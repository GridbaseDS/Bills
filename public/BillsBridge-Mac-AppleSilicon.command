#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
chmod +x "$DIR/billsbridge-macos-arm64"
echo "=================================================="
echo " Iniciando BillsBridge para Mac (Apple Silicon)..."
echo "=================================================="
"$DIR/billsbridge-macos-arm64"
