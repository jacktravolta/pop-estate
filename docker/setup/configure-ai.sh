#!/usr/bin/env bash
set -e

ENV_FILE=".env.ai"
COMPOSE="docker compose"

echo ""
echo "╔══════════════════════════════════════════════╗"
echo "║   POP ESTATE - Configuración de IA           ║"
echo "╚══════════════════════════════════════════════╝"
echo ""
echo "¿Qué tipo de IA quieres usar?"
echo ""
echo "  [1] Nada        → Sin IA (chat y insights desactivados)"
echo "  [2] IA Local    → Ollama en tu máquina (~2-4 GB RAM)"
echo "  [3] OpenAI API  → GPT-4o-mini + embeddings (requiere API key)"
echo ""
read -p "Opción [1/2/3] (default: 1): " CHOICE
CHOICE=${CHOICE:-1}

case "$CHOICE" in
  2)
    AI_MODE="local"
    AI_MODEL="qwen2.5:3b"
    AI_EMBEDDING="nomic-embed-text"
    OPENAI_KEY=""
    echo ""
    echo "📦 IA Local seleccionada"
    echo "   Modelo chat:      $AI_MODEL"
    echo "   Modelo embedding: $AI_EMBEDDING"
    ;;
  3)
    AI_MODE="openai"
    AI_MODEL="gpt-4o-mini"
    AI_EMBEDDING="text-embedding-3-small"
    echo ""
    read -p "🔑 Ingresa tu OpenAI API Key (sk-...): " OPENAI_KEY
    if [[ -z "$OPENAI_KEY" ]]; then
      echo "❌ API key vacía. Abortando."
      exit 1
    fi
    echo "✅ OpenAI API configurada"
    ;;
  *)
    AI_MODE="none"
    AI_MODEL=""
    AI_EMBEDDING=""
    OPENAI_KEY=""
    echo ""
    echo "⚙️  IA desactivada"
    ;;
esac

# Guardar configuración
cat > "$ENV_FILE" << EOF
AI_MODE=$AI_MODE
AI_MODEL=$AI_MODEL
AI_EMBEDDING_MODEL=$AI_EMBEDDING
OPENAI_API_KEY=$OPENAI_KEY
EOF

echo ""
echo "💾 Configuración guardada en $ENV_FILE"

# Aplicar cambios
echo ""
echo "🔄 Aplicando cambios..."

if [ "$AI_MODE" = "local" ]; then
  echo "🐳 Levantando Ollama..."
  $COMPOSE --profile ai up -d ollama 2>/dev/null || $COMPOSE up -d ollama 2>/dev/null || true
  sleep 3
  echo "📥 Descargando modelo de chat ($AI_MODEL)..."
  $COMPOSE exec ollama ollama pull "$AI_MODEL" 2>/dev/null || echo "⚠️  Descarga manual: docker compose exec ollama ollama pull $AI_MODEL"
  echo "📥 Descargando modelo de embeddings ($AI_EMBEDDING)..."
  $COMPOSE exec ollama ollama pull "$AI_EMBEDDING" 2>/dev/null || echo "⚠️  Descarga manual: docker compose exec ollama ollama pull $AI_EMBEDDING"

elif [ "$AI_MODE" = "openai" ]; then
  echo "🛑 Deteniendo Ollama (no necesario con OpenAI)..."
  $COMPOSE stop ollama 2>/dev/null || true

else
  echo "🛑 Deteniendo Ollama..."
  $COMPOSE stop ollama 2>/dev/null || true
fi

# Reiniciar app para que tome la nueva config
echo "🔄 Reiniciando aplicación..."
$COMPOSE restart app 2>/dev/null || true

echo ""
echo "╔══════════════════════════════════════════════╗"
echo "║   Configuración aplicada                     ║"
echo "╠══════════════════════════════════════════════╣"
echo "  Modo:       $AI_MODE"
if [ "$AI_MODE" != "none" ]; then
echo "  Chat:       $AI_MODEL"
echo "  Embeddings: $AI_EMBEDDING"
fi
echo "╚══════════════════════════════════════════════╝"
echo ""
echo "📌 Para cambiar después: ./docker/setup/configure-ai.sh"
echo ""
