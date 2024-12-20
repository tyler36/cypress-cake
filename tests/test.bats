setup() {
  set -eu -o pipefail
  export DIR="$(cd "$(dirname "$BATS_TEST_FILENAME")" >/dev/null 2>&1 && pwd)/.."
  export PROJNAME=test-cypress-cake
  export TESTDIR=~/tmp/test-cypress-cake
  mkdir -p $TESTDIR

  export DDEV_NON_INTERACTIVE=true
  ddev delete -Oy ${PROJNAME} >/dev/null 2>&1 || true
  cd "${TESTDIR}"
}

health_checks() {
  ddev cypress-run | grep "All specs passed"
}

teardown() {
  set -eu -o pipefail

  cd ${TESTDIR} || (printf "unable to cd to ${TESTDIR}\n" && exit 1)
  ddev delete -Oy ${PROJNAME} >/dev/null 2>&1
  [ "${TESTDIR}" != "" ] && rm -rf ${TESTDIR}
}

setupCakePhp() {
  ddev config --project-type=cakephp --docroot=webroot --php-version=8.1
  ddev composer create --prefer-dist --no-interaction cakephp/app:~5.0
}

@test "runs phpunit tests against MySQL database" {
  set -eu -o pipefail

  cd ${TESTDIR}

  # Setup CakePHP
  setupCakePhp
  sed -i 's|^#export DATABASE_TEST_URL=.*|export DATABASE_TEST_URL="mysql://db:db@db/db"|' config/.env

  # Install cypress-cake by setting the preferred path and installing it from there.
  composer config repositories."$(basename "$DIR")" "{\"type\": \"path\", \"url\": \"$DIR\", \"options\": {\"symlink\": false}}" --file composer.json
  composer require tyler36/cypress-cake --ignore-platform-reqs

  # Copy additional settings for PHPunit environment
  cp "$DIR"/tests/testdata/* ${TESTDIR}/ -r
  ddev cake plugin load Tyler36/CypressCake

  # Run PHPunit tests
  ddev exec vendor/bin/phpunit
}

@test "runs phpunit tests on cakephp4" {
  set -eu -o pipefail

  cd ${TESTDIR}

  # Setup CakePHP
  ddev config --project-type=cakephp --docroot=webroot --php-version=7.4
  ddev composer create --prefer-dist --no-interaction cakephp/app:~4.0

  # Install cypress-cake by setting the preferred path and installing it from there.
  composer config repositories."$(basename "$DIR")" "{\"type\": \"path\", \"url\": \"$DIR\", \"options\": {\"symlink\": false}}" --file composer.json
  composer require tyler36/cypress-cake --ignore-platform-reqs

  # Copy additional settings for PHPunit environment
  cp "$DIR"/tests/testdata/* ${TESTDIR}/ -r
  ddev cake plugin load Tyler36/CypressCake

  # Explicitly run migrations
  ddev cake migrations migrate

  # Downgrade PHPunit config for legacy environment. Also ignore deprecations.
  mv phpunit9.xml phpunit.xml.dist
  ddev exec vendor/bin/phpunit
}

@test "runs phpunit tests against Postgres database" {
  set -eu -o pipefail

  cd ${TESTDIR}

  # Setup CakePHP
  ddev config --project-type=cakephp --docroot=webroot --database=postgres:16 --php-version=8.1
  ddev composer create --prefer-dist --no-interaction cakephp/app:~5.0

  # Ensure the database is set to Postgres
  sed -i 's/^\(export DATABASE_URL=\)"[^"]*"/\1"postgres:\/\/db:db@db:5432\/db?encoding=utf8"/' config/.env
  sed -i 's|^#export DATABASE_TEST_URL=.*|export DATABASE_TEST_URL="postgres://db:db@db:5432/db?encoding=utf8"|' config/.env

  # Install cypress-cake by setting the preferred path and installing it from there.
  composer config repositories."$(basename "$DIR")" "{\"type\": \"path\", \"url\": \"$DIR\", \"options\": {\"symlink\": false}}" --file composer.json
  composer require tyler36/cypress-cake --ignore-platform-reqs

  # Copy additional settings for PHPunit environment
  cp "$DIR"/tests/testdata/* ${TESTDIR}/ -r
  ddev cake plugin load Tyler36/CypressCake

  # Run PHPunit tests
  ddev exec vendor/bin/phpunit
}

@test "runs cypress tests" {
  set -eu -o pipefail

  cd ${TESTDIR}

  # Setup CakePHP
  setupCakePhp

  # Copy additional settings for PHPunit environment
  cp "$DIR"/tests/testdata/* ${TESTDIR}/ -r
  ddev cake migrations migrate

  # Install cypress-cake by setting the preferred path and installing it from there.
  composer config repositories."$(basename "$DIR")" "{\"type\": \"path\", \"url\": \"$DIR\", \"options\": {\"symlink\": false}}" --file composer.json
  composer require tyler36/cypress-cake

  # Copy additional settings for PHPunit environment
  cp "$DIR"/tests/testdata/* ${TESTDIR}/ -r
  ddev cake plugin load Tyler36/CypressCake

  ddev addon get tyler36/ddev-cypress
  ddev restart

  # Create a database file to restore in Cypress test.
  echo "INSERT INTO users VALUES (1187404954,'now@example.com','invalid','2024-02-25 09:31:03','2024-06-27 06:34:41');" > tests/backup.sql
  ddev exec cp tests/backup.sql /tmp/test.sql

  echo "import '../../vendor/tyler36/cypress-cake/src/support/cypress-commands'" > cypress/support/commands.js
  ddev cypress-run | grep 'All specs passed'
}
