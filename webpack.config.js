const Encore = require('@terminal42/contao-build-tools');

module.exports = Encore('assets')
    .setOutputPath('public/')
    .setPublicPath('/bundles/cgoitpersons')
    .addStyleEntry('persons-css', './assets/scss/persons.scss')
    .addStyleEntry('backend-css', './assets/scss/backend.scss')
    .getWebpackConfig()
;
