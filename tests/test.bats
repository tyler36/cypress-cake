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

@test "runs phpunit tests" {
  set -eu -o pipefail

  cd ${TESTDIR}

  # Setup CakePHP
  setupCakePhp

  # Install cypress-cake by setting the preferred path and installing it from there.
  composer config repositories."$(basename "$DIR")" "{\"type\": \"path\", \"url\": \"$DIR\", \"options\": {\"symlink\": false}}" --file composer.json
  composer require tyler36/cypress-cake

  # Copy additional settings for PHPunit environment
  cp "$DIR"/tests/testdata/* ${TESTDIR}/ -r
  ddev cake plugin load Tyler36/CypressCake

  # Run PHPunit tests
  ddev exec vendor/bin/phpunit
}
