rem push code

git status
git add .
git commit -am "updated"
git push

rem deploy on server

curl -I https://ai.eteamprojects.com/deploy

pause
