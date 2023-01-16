window.onload = function(){
  var sidenav = document.getElementById("buttons");
  var openBtn= document.getElementById("burger");

  openBtn.onclick = openNav;

  /* Set the width of the side navigation to 250px */
  function openNav() {
    sidenav.classList.add("active");
    openBtn.classList.add("close");
    openBtn.onclick = closeNav;
  }

  /* Set the width of the side navigation to 0 */
  function closeNav() {
    sidenav.classList.remove("active");
    console.log("test");
    openBtn.classList.remove("close");
    openBtn.onclick = openNav;
  }
};