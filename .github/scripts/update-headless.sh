#!/usr/bin/env bash

if [ -z $GITHUB_TOKEN ]; then
    echo "GITHUB_TOKEN missing from env"

    exit 1
fi

git clone https://${GITHUB_ACTOR}:${GITHUB_TOKEN}@github.com/${GITHUB_REPOSITORY}.git headless-gh -b headless
rm headless-gh/* -rf
rm headless/Dockerfile
cp headless/* headless-gh/ -r
cp headless/.gitignore headless-gh/
cp headless/.env.example headless-gh/
cp storage/ headless-gh/ -r

if [[ -z `git -C headless-gh status --short` ]]; then
    echo "No changes to apply!"

    exit;
fi

cd headless-gh
git config user.name 'github-actions[bot]'
git config user.email 'github-actions[bot]@users.noreply.github.com'
git add -A
git commit -m '[github-actions] Update'
git push

echo "Headless branch updated!"
