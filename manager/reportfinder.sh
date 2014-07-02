#!/bin/bash
NEWREPORTS=`find /home/turtleminder -mmin -15`

DATE=`date`

echo "$DATE : FOUND  $NEWREPORTS" >> /opt/turtleminder/manager/log/reportlogs

echo `/opt/turtleminder/parser/parser.php $NEWREPORTS` >> /opt/turtleminder/manager/log/reportlogs

