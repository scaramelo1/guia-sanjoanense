#!/bin/bash
# Publica um APK novo do Guia Sanjoanense no site (botão "Baixar APK" de https://fsadesigners.com.br/guia-sanjoanense/).
# Rodar como root na VPS. Aceita o arquivo .apk ou o link que o EAS mostra no fim do build:
#   bash servidor/publicar-apk.sh https://expo.dev/artifacts/eas/XXXX.apk
#   bash servidor/publicar-apk.sh /root/guia-sanjoanense.apk
# O total de downloads nunca é zerado. A versão vem do app.json (ou do 2º argumento).
set -euo pipefail

RAIZ="$(cd "$(dirname "$0")/.." && pwd)"
DADOS=$(php -r 'echo rtrim((require "/etc/guia-sanjoanense/config.php")["dados_dir"] ?? "/var/lib/guia-sanjoanense", "/");')
DIR="$DADOS/apk"
ORIGEM="${1:-}"
VERSAO="${2:-$(php -r 'echo json_decode(file_get_contents($argv[1]), true)["expo"]["version"] ?? "";' "$RAIZ/app.json")}"

if [ -z "$ORIGEM" ]; then
  echo "Uso: bash servidor/publicar-apk.sh <arquivo.apk | link do EAS> [versão]"
  exit 1
fi

mkdir -p "$DIR"
NOVO="$DIR/guia-sanjoanense.apk.novo"
if [[ "$ORIGEM" =~ ^https:// ]]; then
  echo "Baixando o APK..."
  curl -fL --retry 3 -m 900 -o "$NOVO" "$ORIGEM"
else
  cp "$ORIGEM" "$NOVO"
fi

# Confere se é mesmo um APK (arquivo zip com AndroidManifest.xml) antes de trocar
if [ "$(head -c 2 "$NOVO")" != "PK" ] || ! unzip -l "$NOVO" 2>/dev/null | grep -q AndroidManifest.xml; then
  rm -f "$NOVO"
  echo "Falhou: o arquivo não é um APK. Nada foi trocado."
  exit 1
fi

mv "$NOVO" "$DIR/guia-sanjoanense.apk"          # troca de uma vez: ninguém baixa pela metade
echo "$VERSAO" > "$DIR/versao.txt"
[ -f "$DIR/downloads.txt" ] || echo 0 > "$DIR/downloads.txt"
chown -R www-data:www-data "$DIR"
chmod 750 "$DIR"

echo "APK:        $(du -h "$DIR/guia-sanjoanense.apk" | cut -f1), versão ${VERSAO:-?}"
echo "Downloads:  $(cat "$DIR/downloads.txt") até agora"
echo "Página:     $(curl -s -o /dev/null -w '%{http_code}' -m 10 https://fsadesigners.com.br/guia-sanjoanense/)"
echo "Baixar:     $(curl -s -I -o /dev/null -w '%{http_code} %{content_type}' -m 10 https://fsadesigners.com.br/guia-sanjoanense/baixar-apk.php)"
echo "Link:       https://fsadesigners.com.br/guia-sanjoanense/baixar-apk.php"
