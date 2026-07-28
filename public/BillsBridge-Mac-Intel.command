#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
chmod +x "$DIR/billsbridge-macos-x64"
echo "=================================================="
echo " Iniciando BillsBridge para Mac (Intel)..."
echo "=================================================="
"$DIR/billsbridge-macos-x64"
