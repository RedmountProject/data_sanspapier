
if (( $EUID -ne $ROOT_EUID ));
then
	echo "ROOT OR NOT"
	exit 1
fi
	php app/console cache:clear
	php app/console doctrine:database:drop --force --connection user
	php app/console doctrine:database:drop --force --connection shop
	php app/console doctrine:database:create --connection user
	php app/console doctrine:database:create --connection shop
	php app/console doctrine:schema:create --em="user"
	php app/console doctrine:schema:create --em="shop"
	chmod -R 777 ./app/cache
	chmod -R 777 ./app/logs
	php app/console doctrine:fixtures:load --em="user"
	
