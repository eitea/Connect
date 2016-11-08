document.onclick= function(event) {
  parent.document.getElementById("myFrame").style.height = this.contentWindow.document.body.scrollHeight + 'px';
};
