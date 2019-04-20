MEVO WATCHDOG
=========

## Prerequisites

You need to have following installed:

   * Linux-based operating system
   * [Docker]
   * [Docker Compose]

After installing Docker Compose it's strongly recommended to add alias for this command.
Just add following line to your `~/.bash_aliases` file and restart your terminal.

```
alias dcp="sudo -E docker-compose"
```

To make life easier you can also add two more aliases:
```
alias dcomposer="sudo -E docker-compose exec php composer"
alias dphp="sudo -E docker-compose exec php php"
```
Those aliases will execute `dphp ...` and `dcomposer ...` commands inside the PHP container.

## Installation

Checkout project from repository and execlude following commands:
```
cp .env.dist .env
dcp up -d
dcomposer install
dphp bin/console doctrine:schema:update --force
```

Go to `localhost:8880` to see if it works. If page shows up, use `dphp bin/console mevo:fetch` to get fresh data about bikes.
Run `dphp bin/console mevo:fetch:stations` to fetch stations data.
Cronjob for that command is embeded in php container, it will fire up every 5 minutes.