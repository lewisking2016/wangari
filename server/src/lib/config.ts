// Central product constants — change here, applies everywhere.
// (Kept in code, not DB, because it gates access on boot before any DB call.)
export const TRIAL_DAYS = 14;

export function trialEndDate(from: Date = new Date()): Date {
  return new Date(from.getTime() + TRIAL_DAYS * 24 * 60 * 60 * 1000);
}
