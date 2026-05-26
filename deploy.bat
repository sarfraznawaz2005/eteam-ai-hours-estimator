rem push code

git status
git add .
git commit -am "updated"
git push

rem deploy on server

curl https://ai.eteamid.com/deploy.php

pause
