# Creazione nuovo partner
#aggiorna
1) su mysql produzione aggiungere un nuovo database per convenzione il db sarà nominato con il nome del partner. 
    Esempio partner ScommesseItalia db scommesseitalia
2) Portarsi nella cartella unica di progetto Isibet  e controllare che tutti i branch siano su main. (6 progetti)
3) creare sul server di nella home di administrator la cartella che conterrà le immagini docker del nuovo partner es.: 
`mkdir -p sommesseitalia/images`
4) creare i docker necessari all'ambiente di produzione (le operazioni seguenti si eseguono sul proprio pc):
    - php
    - nginx - api
    - nginx - viewer
    ### creazione docker:
    1) dalla cartella isibet-app e partendo da main creare un branch con il nome del partner `git checkout -b scommesseitalia` 
    2) Impostare i parametri del db nel file .env di laravel
    3) Duplicare la cartella localstart con il nomepartner oppure se già presente rinominare la cartella del partner. Se la cartella del partner è già presente fare attenzione alle immagini presenti nella cartella images. Eliminarle tutte.
    4) portarsi nella cartella del partner, modificare il file create.sh  avendo cura di impostare le variabili al suo interno,
    5) modificare il rigo 32 nel file `./isibet-app/localstart/nginx/default.conf` Modificare la parola pgv-php con "prime3letterepartner"-php . Fare stessa midifica nel file `./isibet-app/rootfs/etc/nginx/default.conf`
    5.5) impostare il token GitHub per composer `export GITHUB_TOKEN=ghp_yourvalidtokenhere`

    6) eseguire il comando `sudo chmod +x create.sh` ed eseguire lo script  `./create.sh` ,  il comando crearà le immagini nella cartella images
    7) spostarsi nella cartella delle immagini docker `cd images`
    8) inviare le immagini appena create e il docker-compose-server.yaml sul server di produzione:
        - `scp sco-php.tar administrator@10.200.1.87:/home/administrator/scommesseitalia/images/sco-php.tar`
        - `scp sco-pgv.tar administrator@10.200.1.87:/home/administrator/scommesseitalia/images/sco-pgv.tar`
        - `scp docker-compose-server.yml administrator@10.200.1.87:/home/administrator/scommesseitalia/docker-compose.yml`
   
    9) Aggiornare / Creare immagine Viewer
    10) spostarsi nella cartella del viewer e copiare i css e i font dogs6 del partner dentro dist_templates
    11) controllare l'indirizzo api inserito nel file Dockerfile e in ./scripts/dist.sh
    12) controlare nome partner in dockerfile V_VIEWER_DOGS6_PARTNER
    13) eseguire `docker build -t sco-viewer:latest .` dare nuovo nome (rispettare la convenzione "primi3lettere partner"-viewer )
    14) eseguire `docker save -o sco-viewer.tar sco-viewer:latest` dare nuovo nome
    15) inviare l'immagine del viewer appena create sul server di produzione:
        - `scp sco-viewer.tar administrator@10.200.1.87:/home/administrator/scommesseitalia/images/sco-viewer.tar`
        
    16) accedere al server nella cartella del partner/images e caricare le immagini docker:
        - `docker load -i sco-pgv.tar`
        - `docker load -i sco-php.tar`
        - `docker load -i sco-viewer.tar`
    17) ambiente di produzione pronto accedere al server e eseguire `docker-compose up -d`
    18) collegarsi all'istanza php e lanciare le migrazioni con seed: `docker exec -it sco-php php artisan migrate --seed`
    19) eliminare tutte le immagini create prima di fare commit o push.
    20) nel caso di errori di avvio verificare che tutti i .sh hanno il permesso +x di esecuzione
    --------------------------------- FINE ---------------------------------
    * FARE OPPORTUNE VERIFICHE AL DB