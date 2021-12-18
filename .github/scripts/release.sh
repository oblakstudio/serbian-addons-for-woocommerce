#/bin/bash

PROJECT_NAME="serbian-addons-for-woocommerce"
NEXT_VERSION=$1
CURRENT_VERSION=$(cat $PROJECT_NAME.php | grep "Version" | head -1 | awk -F= "{ print $2 }" | sed 's/[* Version:,\",]//g' | tr -d '[[:space:]]')

sed -i "s/Version:              $CURRENT_VERSION/Version:              $NEXT_VERSION/g" serbian-addons-for-woocommerce.php
sed -i "/Stable tag: $CURRENT_VERSION/Stable tag: $NEXT_VERSION/g" .wordpress-org/readme.txt

rm -f /tmp/release.zip
mkdir /tmp/$PROJECT_NAME
cp -ar config dist languages lib vendor ./*.php loco.xml /tmp/$PROJECT_NAME 2>/dev/null
cp ./.wordpress-org/readme.txt /tmp/$PROJECT_NAME 2>/dev/null

cd /tmp
zip -qr /tmp/release.zip ./*.php $PROJECT_NAME
