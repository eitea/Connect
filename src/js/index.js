console.log("webpack works");

require("./dsgvo-training"); // execute dsgvo-training

//only do this until all js uses webpack
window.jQuery = require("jquery");
window.$ = require("jquery");
