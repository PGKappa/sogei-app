# k8s-app

kubectl exec xxxxxxx -n pgvirtual-dev -- sh -c "php /var/www/html/artisan pg:generateEvents > /dev/null 2> /dev/null &"

kubectl exec xxxxxxx -n pgvirtual-dev -- sh -c "php /var/www/html/artisan pg:generateEvents --today > /dev/null 2> /dev/null &"

kubectl exec xxxxxxx -n pgvirtual-dev -- sh -c "php /var/www/html/artisan pg:TicketPaymentEngine --debug > /dev/null 2> /dev/null &"

kubectl exec xxxxxxx -n pgvirtual-dev -- sh -c "php /var/www/html/artisan pg:generateResults > /dev/null 2> /dev/null &"

OLD DOCS

# Aggiornamento  

# PGVirtual


## Start the project
Run the script `./dev/linux/start.sh` to execute the docker-compose.


Services pull docker images from company's private registries under `registry.git.xpegma.eu`.
The tokens required for downloading the images are provided in the files `image_registry_*token.txt` in the project root folder.

### Start the project - Using an external instance of v-ui
In the file `./dev/linux/start.sh` set the variable `LOCAL_DEV__V_UI=true`

## Custom Configurations
PGVirtual supports many configurations to customise its setup. 
Configurations can be added as rows in the `configurations` table of the database.

| Ex. | Description | `configurations`<br>\`key\` | `configurations`<br>\`operator_id\` | `configurations`<br>\`game_id\` | `configurations`<br>\`currency_id\` | `configurations`<br>\`channel_id\` |`configurations`<br>\`value\` |
|:---:|---|---|:---:|:---:|:---:|:---:|---|
|1| Enable channel 1 for operator 1 | **ENABLED_CHANNELS_FOR_OPERATOR** | 1 | <i>NULL</i> | <i>NULL</i> | 1 | `true` (string) |
|2|Custom DayConfig for scheduling events of channel 1 for operator 1. The value must always be a string of a json encoded object containing the properties `firstEventTime`, `eventDuration` and `eventsCount`.  | **DAY_CONFIG** | 1 | <i>NULL</i> | <i>NULL</i> | 1 | ```{"firstEventTime":0, "eventDuration":240, "eventsCount":360}```|
|3| Viewers - Duration of Page Main for the channel 3 of operator 1  | **PAGE_MAIN_DURATION** | 1 |<i>NULL</i>| <i>NULL</i> | 3 | ```15```|
> Default values are defined into the file `app/Libraries/EventSchedulingConfig.php`, in the function `public static function get($channel, $dayNumber)` itself.



### Add a new video set
- create a file following this grammar:

```
game: <gamename>
racers: <numRacers>
<filename1>.<format> <duration>
<filename2>.<format> <duration>
<filename3>.<format> <duration>
...
<filenameN>.<format> <duration>
```

> When the duration is not specified, default value is 50.

Example 1:
```
game: dogs 
racers: 6
123a.mp4
123b.mp4
123c.mp4
...
```

Example 2:
```
game: horses
racers: 6
123.mp4 127
123A.mp4 126
123B.mp4 104
...
```



- copy the file into `database/seeders/videosets` folder
- append the filename to `$VIDEOSET_FILES` list of run() method in `VideosSeeder.php` 

# isibet-app
Copia di k8s-app per Isibet 

rev. 1
02-11 revision cashier init in dog e horses, blocco eventi che non hanno palinsesto ams
03-11 echo su generate result dei dogs
