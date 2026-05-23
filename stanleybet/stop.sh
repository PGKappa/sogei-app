#!/bin/bash

echo 'fermo nginx'
docker stop pgv

echo 'Fermo php...'
docker stop pgv-php

echo 'fermo mysql'
docker stop pgvmysql

echo 'fermo phpmyadmin'
docker stop pgv-phpmyadmin

