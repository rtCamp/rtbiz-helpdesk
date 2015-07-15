#!/bin/bash
# Edit your variable here here is url for my project git.rtcamp.com/rtbiz/rtbiz-helpdesk where rtbiz is team and rtbiz-helpdesk is project
# This will give you final zip file which you may use as release version. it will remove unnecessary development files from zip.
gitLab="http://git.rtcamp.com"
team="rtbiz"
project="rtbiz-helpdesk"

read -p "User name: " login
echo -n Password:
read -s password
echo

function jsonValue() {
	KEY=$1
	num=$2
	awk -F"[,:}]" '{for(i=1;i<=NF;i++){if($i~/'$KEY'\042/){print $(i+1)}}}' | tr -d '"' | sed -n ${num}p
}

token=$(curl -s "$gitLab/api/v3/session/" --data "login=$login&password=$password" | jsonValue private_token)
if [ -z "$token" ]; then
	echo "Please check your auth or internet connection"
	exit 1
fi
read -p "Please enter tag number or branch name: " input_variable
BASEDIR=$(dirname $0)
cd $BASEDIR
if [ ! -d "$input_variable" ]; then
	mkdir $input_variable
fi
cd $input_variable
if [ -f "$input_variable.zip" ]; then
	rm $input_variable.zip
fi
curl -s $gitLab/$team/$project/repository/archive.zip\?ref\=$input_variable\&\private_token\=$token -o $input_variable.zip
if [ -d "$project" ]; then
	rm -rf $project
fi
unzip $input_variable.zip -d $project >/dev/null
cd $project
cd $project.git
#add development files here to remove from production
rm -rf .gitignore .gitlab-ci.yml .editorconfig .jshintignore build tests config.rb gruntfile.js phpunit.xml package.json
cd ..
mv $project.git $project
zip -rq $project.zip $project
cp $project.zip ../.
cd ..
rm -rf $input_variable.zip $project
echo "Output : $(pwd)/$project.zip"