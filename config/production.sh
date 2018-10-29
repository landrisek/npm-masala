sudo rm /var/www/4camping.cz/lubo/temp/cache -Rf
sudo rm /var/www/4camping.cz/lubo/assets/masala/js -Rf
../../../node_modules/.bin/webpack -p --config webpack.prod.js