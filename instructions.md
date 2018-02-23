# Using npm/yarn

## Download Node

Download nodejs from here: https://nodejs.org/en/download/

## Download Dependencies

Run `npm install` to install all dependencies and `npm start` to build the bundle. 

## Other Instructions

The starting point for webpack is in /src/js/index.js and the output goes to plugins. 

If you want to make packages available globally (outside webpack), use the https://webpack.js.org/loaders/expose-loader/ or:

Your browser may cache the bundle file. To prevent this, go to DevTools > network tab > check "Disable cache".

Add new packages with `npm i package-name`.

```js
const somePackage = require("some-package");
window.somePackage = somePackage; // somePackage would be available everywhere (this will be removed after all existing js is defined in /src/js)
```
