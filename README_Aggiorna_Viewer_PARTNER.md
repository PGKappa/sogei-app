# Aggiorna Viewer Partner

    1) spostarsi nella cartella del viewer e copiare i css e dogs6 del partner dentro dist_templates
    2) copiare la cartella font all'interno della cartella partner appena copiata  
    3) controllare l'indirizzo api inserito nel file Dockerfile e in ./scripts/dist.sh
    4) controlare nome partner in dockerfile V_VIEWER_DOGS6_PARTNER
    5) eseguire `docker build -t sco-viewer:latest .` dare nuovo nome (rispettare la convenzione "primi3lettere partner"-viewer )
    6) eseguire `docker save -o sco-viewer.tar sco-viewer:latest` dare nuovo nome e rispettare convenzione
    7) inviare l'immagine del viewer appena create sul server di produzione:
        - `scp sco-viewer.tar administrator@10.200.1.87:/home/administrator/scommesseitalia/images/sco-viewer.tar`
    8) accedere al server nella cartella del partner/images e caricare le immagini docker:
        - `docker load -i sco-viewer.tar`
    9) cd .. per portarsi nella root del partner (solo se possibile, per una maggiore sicurezza cancellare su produzione immagini e fermare ed eliminare il docker del viewer)
    10) ambiente di produzione pronto accedere al server e eseguire `docker-compose up -d`
    11) eliminare tutte le immagini create prima di fare commit o push.



    --------------------------------- FINE ---------------------------------