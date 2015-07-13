function changeDfpValue(e, e_min, e_max) {
    var value = e.options[e.selectedIndex].getAttribute("data-width");
    document.getElementById(e_min).setAttribute("min", value);
    document.getElementById(e_max).setAttribute("min", value);
    document.getElementById(e_min).setAttribute("value", value);
    document.getElementById(e_max).setAttribute("value", '');
}