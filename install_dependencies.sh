#!/bin/sh

sudo apt -y install php php-curl php-bcmath php-json php-mysql php-mbstring php-xml php-tokenizer php-zip libapache2-mod-php php-sqlite3 composer npm
composer install
npm install

