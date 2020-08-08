#!/bin/bash

docker run --rm --interactive --tty \
  --user $(id -u):$(id -g) \
  --volume $PWD:/app \
  --volume $SSH_AUTH_SOCK:/ssh-auth.sock \
  --env SSH_AUTH_SOCK=/ssh-auth.sock \
  composer bash
