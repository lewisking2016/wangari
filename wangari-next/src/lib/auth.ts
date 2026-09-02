import NextAuth from "next-auth";
import Google from "next-auth/providers/google";
import Credentials from "next-auth/providers/credentials";
import bcrypt from "bcryptjs";
import { prisma } from "@/lib/db";

export const { handlers, auth, signIn, signOut } = NextAuth({
  session: { strategy: "jwt" },
  pages: {
    signIn: "/login",
  },
  providers: [
    Google({
      clientId: process.env.AUTH_GOOGLE_ID!,
      clientSecret: process.env.AUTH_GOOGLE_SECRET!,
    }),
    Credentials({
      name: "credentials",
      credentials: {
        email: { label: "Email", type: "email" },
        password: { label: "Password", type: "password" },
      },
      async authorize(credentials) {
        if (!credentials?.email || !credentials?.password) return null;

        const user = await prisma.user.findUnique({
          where: { email: credentials.email as string },
        });

        if (!user) return null;

        const isValid = await bcrypt.compare(
          credentials.password as string,
          user.password
        );

        if (!isValid) return null;

        return {
          id: String(user.id),
          name: user.name,
          email: user.email,
          role: user.role,
        };
      },
    }),
  ],
  callbacks: {
    async signIn({ user, account }) {
      // For Google OAuth: create or find user in database
      if (account?.provider === "google" && user?.email) {
        try {
          const existingUser = await prisma.user.findUnique({
            where: { email: user.email },
          });

          if (!existingUser) {
            // Create new user from Google profile
            const newUser = await prisma.user.create({
              data: {
                name: user.name || "Google User",
                email: user.email,
                password: "", // No password for Google users
                role: "farm_owner",
              },
            });

            // Create a default farm for the new user
            await prisma.farm.create({
              data: {
                name: `${user.name}'s Farm`,
                ownerId: newUser.id,
                location: "Nairobi, Kenya",
              },
            });

            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            (user as any).id = String(newUser.id);
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            (user as any).role = newUser.role;
          } else {
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            (user as any).id = String(existingUser.id);
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            (user as any).role = existingUser.role;
          }
        } catch (error) {
          console.error("Google sign-in error:", error);
          return false;
        }
      }
      return true;
    },
    async jwt({ token, user }) {
      if (user) {
        token.id = user.id;
        token.role = (user as any).role;
      }
      return token;
    },
    async session({ session, token }) {
      if (session.user) {
        session.user.id = token.id as string;
        (session.user as any).role = token.role;
      }
      return session;
    },
  },
});
