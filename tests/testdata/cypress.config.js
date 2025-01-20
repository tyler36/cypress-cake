module.exports = {
  e2e: {
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
  },
  screenshotOnRunFailure: true,
  screenshotsFolder: 'logs/e2e/screenshots',
  video: true,
  videosFolder: 'logs/e2e/videos',
};
