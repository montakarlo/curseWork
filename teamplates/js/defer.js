function pow2(){
  var path = document.getElementById("txt").value;
  // PictureUl = "<li data-src='img/p5.jpg' data-sub-html='Picture'><a href='#'><img class='sizes' src= 'img/p5.jpg' /></a></li> ";
  var PictureUl = "<li data-src='"+path+"' data-sub-html='Picture'><a href='#'><img class='sizes' src= '"+path+"' /></a></li> ";
  $(".gallery").append(PictureUl);
};
