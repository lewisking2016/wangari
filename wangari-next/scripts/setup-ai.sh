#!/bin/bash
# ============================================
# Wangari AI — Provider Setup Guide
# ============================================

echo "=== Wangari AI — Provider Setup ==="
echo ""
echo "Choose your AI provider and add these to your server .env file:"
echo ""

echo "--- Option 1: Google Gemini (FREE) ---"
echo "1. Go to https://aistudio.google.com/apikey"
echo "2. Create a free API key"
echo "3. Add to ~/wangari/server/.env:"
echo "   AI_PROVIDER=gemini"
echo "   AI_API_KEY=your-gemini-api-key"
echo "   AI_MODEL=gemini-2.0-flash"
echo ""

echo "--- Option 2: OpenAI (Cheapest) ---"
echo "1. Go to https://platform.openai.com/api-keys"
echo "2. Create an API key"
echo "3. Add to ~/wangari/server/.env:"
echo "   AI_PROVIDER=openai"
echo "   AI_API_KEY=your-openai-api-key"
echo "   AI_MODEL=gpt-4o-mini"
echo ""

echo "--- Option 3: Anthropic Claude ---"
echo "1. Go to https://console.anthropic.com/"
echo "2. Create an API key"
echo "3. Add to ~/wangari/server/.env:"
echo "   AI_PROVIDER=anthropic"
echo "   AI_API_KEY=your-anthropic-api-key"
echo "   AI_MODEL=claude-3-haiku-20240307"
echo ""

echo "--- Option 4: Ollama (Local, needs 2GB+ RAM) ---"
echo "1. Install Ollama: curl -fsSL https://ollama.com/install.sh | sh"
echo "2. Pull model: ollama pull qwen2.5:1.5b"
echo "3. Add to ~/wangari/server/.env:"
echo "   AI_PROVIDER=ollama"
echo "   OLLAMA_URL=http://127.0.0.1:11434"
echo "   AI_MODEL=qwen2.5:1.5b"
echo ""

echo "After adding the env vars, restart the server:"
echo "  cd ~/wangari/server && npm run build && npm start"
