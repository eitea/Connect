//only do this until all js uses webpack
window.jQuery = require("jquery");
window.$ = require("jquery");
console.log("webpack works");

require("./dsgvo-training"); // execute dsgvo-training

import Noty from 'noty';

window.Noty = Noty
setTimeout(() => {
    let note = new Noty({
        text: 'Some notification text',
        container: ".is-table-row"
    }).show();
}, 1000)
