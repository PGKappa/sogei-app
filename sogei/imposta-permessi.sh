#!/bin/bash

# Specifica la cartella da scansionare
cartella=".."

# Scorre tutti i file nella cartella e nelle sottocartelle
find "$cartella" -type f -name "*.sh" -exec chmod +x {} \;

echo "Permessi eseguibili impostati per tutti i file .sh in $cartella"