function deploy() {
	cd ~/plugins/stream-manager
	git checkout master
	git pull
	cd ~/plugins/stream-manager-wp

	# Add new tag
	echo 'Adding new tag'
	mkdir tags/$1
	cp -r ~/plugins/stream-manager/assets tags/$1/assets
	cp -r ~/plugins/stream-manager/includes tags/$1/includes
	cp ~/plugins/stream-manager/README.txt tags/$1/README.txt
	cp ~/plugins/stream-manager/README.md tags/$1/README.md
	cp ~/plugins/stream-manager/stream-manager.php tags/$1/stream-manager.php
	svn add tags/$1
	svn commit -m "Updating to $1"

	# Update the trunk
	echo 'Updating the trunk'
	cd ~/plugins/stream-manager-wp/trunk
	rm -rf ~/Sites/stream-manager-wp/trunk/includes
	rm -rf ~/Sites/stream-manager-wp/trunk/assets
	cp -r ~/plugins/stream-manager/assets ~/plugins/stream-manager-wp/trunk/assets
	cp -r ~/plugins/stream-manager/includes ~/plugins/stream-manager-wp/trunk/includes
	cp ~/plugins/stream-manager/README.txt ~/plugins/stream-manager-wp/trunk/README.txt
	cp ~/plugins/stream-manager/README.md ~/plugins/stream-manager-wp/trunk/README.md
	cp ~/plugins/stream-manager/stream-manager.php ~/plugins/stream-manager-wp/trunk/stream-manager.php
	svn add --force ./*
	svn commit -m "updating to $1"
	# check if the above works on next release
	# svn commit -m "updating to $1" readme.txt
	# svn commit -m "updating to $1" timber.php
}

#!/usr/bin/env bash
read -p "Tag Number: " tag

if [[ -z "$tag" ]]; then
   printf '%s\n' "No tag entered"
   exit 1
else
   deploy $tag 
fi
