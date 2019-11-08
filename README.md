# beerboard

## Create the database
Run php beer.php --schema to generate database schema

## Using the CLI
Run php beer.php --help for instructions

## Using the web-service:
Curl examples - but any REST client should do...

### Add beer
Add a beer to a user
* curl http://localhost:8080/beerServiccurl -X POST -d "action=addbeer&cardid=0x0004"

Add a beer to a user - including tap ID
* curl http://localhost:8080/beerServiccurl -X POST -d "action=addbeer&cardid=0x0004&tap=2"

Add a beer to a user - including tap ID and volume
* curl http://localhost:8080/beerServiccurl -X POST -d "action=addbeer&cardid=0x0004&tap=2&volume=500"

### Get user
* curl http://localhost:8080/beerService.php?action=getuser\&cardid='0x003'

### Get top users
Get top 10 user
* curl http://localhost:8080/beerService.php?action=topusers\&num=10

Get top 1 user
* curl http://localhost:8080/beerService.php?action=topusers\&num=1


### Get beer log
Get most recent 3 beers
* curl http://localhost:8080/beerService.php?action=beerlog\&num=3

Get most recent 100 beers
* curl http://localhost:8080/beerService.php?action=beerlog\&num=3


