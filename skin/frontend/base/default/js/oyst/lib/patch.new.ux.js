var oldSelect = Element.select;

Element.addMethods({
  select: function(elem1, expr1) {
    var oldResult = oldSelect(elem1, expr1);
    var result = new Array;

    oldResult.each(function(elem2){
      if(elem2 !== false) {
        result.push(elem2);
      }
    });

    return result;
  }
});