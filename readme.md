#PASOS DE INSTALACION
1. git clone repo_url
2. Composer install


3. Copy paste .env.example to .env and change permissions to db local
    3.1 APP_URL=http://127.0.0.1:8000
    3.2 base de datos de configuración
4. php artisan migrate
5. php artisan key:generate
6. php artisan jwt:secret


#levantar el servidor
php artisan serve 



#GIT shorcuts


git pull origin bran_deseada (traemos el código del servidor)

git status (muestra los cambios realizados luego de un pull) + ademas muestra la branch donde estoy parado

## subiendo cambios al servidor
git add . (agrega todos los archivos q hayan sufrido alguna modificación/ sino se puede hacer con el nombre del archivo ej: 'git add readme.md')
git commit -m "mensaje de los cambios"
## subiendo cambios al servidor cloud
git push origin branch_destino


##branches
git branch (muestra las branch disponibles)
git branch nueva_branch_nombre ()
git checkout branch_nombre (SIEMRPE HACER UN commit antes de hacer checkout de una branch SIMEPRE!!!!!!!!!)


#TERMINA GIT


