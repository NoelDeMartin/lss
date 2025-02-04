#!/usr/bin/env bash

if [ -z $GITHUB_TOKEN ]; then
    echo "GITHUB_TOKEN missing from env"

    exit 1
fi

git clone https://${GITHUB_ACTOR}:${GITHUB_TOKEN}@github.com/${GITHUB_REPOSITORY}.git kanjuro-gh -b kanjuro
rm kanjuro-gh/* -rf
rm kanjuro/Dockerfile
cp kanjuro/* kanjuro-gh/ -r
cp kanjuro/.gitignore kanjuro-gh/
cp kanjuro/.env.example kanjuro-gh/
cp storage/ kanjuro-gh/ -r

if [[ -z `git -C kanjuro-gh status --short` ]]; then
    echo "No changes to apply!"

    exit;
fi

cd kanjuro-gh
git config user.name 'github-actions[bot]'
git config user.email 'github-actions[bot]@users.noreply.github.com'
git add -A
git commit -m '[github-actions] Update'
git push

echo "kanjuro branch updated!"
