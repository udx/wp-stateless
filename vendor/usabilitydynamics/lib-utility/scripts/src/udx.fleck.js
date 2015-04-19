/**
 * fleck - functional style string inflections
 * https://github.com/trek/fleck
 * copyright Trek Glowacki
 * MIT License
 *
 * @version 1.0
 * @description functional style string inflections
 * @package fleck
 * @author Trek Glowacki
 */
  
!function (name, definition) {
  if (typeof module != 'undefined') module.exports = definition()
  else if (typeof define == 'function' && typeof define.amd == 'object') define(definition)
  else this[name] = definition()
}('fleck', function () {
  
  var lib = {
    // plural rules, singular rules, and starting uncountables
    // from http://code.google.com/p/inflection-js/
    // with corrections for ordering and spelling
    pluralRules: [
      [new RegExp('(m)an$', 'gi'),                 '$1en'],
      [new RegExp('(pe)rson$', 'gi'),              '$1ople'],
      [new RegExp('(child)$', 'gi'),               '$1ren'],
      [new RegExp('^(ox)$', 'gi'),                 '$1en'],
      [new RegExp('(ax|test)is$', 'gi'),           '$1es'],
      [new RegExp('(octop|vir)us$', 'gi'),         '$1i'],
      [new RegExp('(alias|status)$', 'gi'),        '$1es'],
      [new RegExp('(bu)s$', 'gi'),                 '$1ses'],
      [new RegExp('(buffal|tomat|potat)o$', 'gi'), '$1oes'],
      [new RegExp('([ti])um$', 'gi'),              '$1a'],
      [new RegExp('sis$', 'gi'),                   'ses'],
      [new RegExp('(?:([^f])fe|([lr])f)$', 'gi'),  '$1$2ves'],
      [new RegExp('(hive)$', 'gi'),                '$1s'],
      [new RegExp('([^aeiouy]|qu)y$', 'gi'),       '$1ies'],
      [new RegExp('(matr|vert|ind)ix|ex$', 'gi'),  '$1ices'],
      [new RegExp('(x|ch|ss|sh)$', 'gi'),          '$1es'],
      [new RegExp('([m|l])ouse$', 'gi'),           '$1ice'],
      [new RegExp('(quiz)$', 'gi'),                '$1zes'],
      [new RegExp('s$', 'gi'),                     's'],
      [new RegExp('$', 'gi'),                      's']
    ],
    singularRules: [
      [new RegExp('(m)en$', 'gi'),                                                       '$1an'],
      [new RegExp('(pe)ople$', 'gi'),                                                    '$1rson'],
      [new RegExp('(child)ren$', 'gi'),                                                  '$1'],
      [new RegExp('([ti])a$', 'gi'),                                                     '$1um'],
      [new RegExp('((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$','gi'), '$1$2sis'],
      [new RegExp('(hive)s$', 'gi'),                                                     '$1'],
      [new RegExp('(tive)s$', 'gi'),                                                     '$1'],
      [new RegExp('(curve)s$', 'gi'),                                                    '$1'],
      [new RegExp('([lr])ves$', 'gi'),                                                   '$1f'],
      [new RegExp('([^fo])ves$', 'gi'),                                                  '$1fe'],
      [new RegExp('([^aeiouy]|qu)ies$', 'gi'),                                           '$1y'],
      [new RegExp('(s)eries$', 'gi'),                                                    '$1eries'],
      [new RegExp('(m)ovies$', 'gi'),                                                    '$1ovie'],
      [new RegExp('(x|ch|ss|sh)es$', 'gi'),                                              '$1'],
      [new RegExp('([m|l])ice$', 'gi'),                                                  '$1ouse'],
      [new RegExp('(bus)es$', 'gi'),                                                     '$1'],
      [new RegExp('(o)es$', 'gi'),                                                       '$1'],
      [new RegExp('(shoe)s$', 'gi'),                                                     '$1'],
      [new RegExp('(cris|ax|test)es$', 'gi'),                                            '$1is'],
      [new RegExp('(octop|vir)i$', 'gi'),                                                '$1us'],
      [new RegExp('(alias|status)es$', 'gi'),                                            '$1'],
      [new RegExp('^(ox)en', 'gi'),                                                      '$1'],
      [new RegExp('(vert|ind)ices$', 'gi'),                                              '$1ex'],
      [new RegExp('(matr)ices$', 'gi'),                                                  '$1ix'],
      [new RegExp('(quiz)zes$', 'gi'),                                                   '$1'],
      [new RegExp('s$', 'gi'),                                                           '']
    ],
    uncountableWords: {
      'equipment': true,
      'information': true,
      'rice': true,
      'money': true,
      'species': true,
      'series':true,
      'fish':true,
      'sheep':true,
      'moose':true,
      'deer':true, 
      'news':true
    },
    // Chain multiple inflections into a signle call
    // Examples:
    //   lib.inflect('     posts', 'strip', 'singularize', 'capitalize') == 'Post'
    inflect: function(str){
      for (var i = 1, l = arguments.length; i < l; i++) {
        str = lib[arguments[i]](str);
      };

      return str;
    },
    // Uppercases the first letter and lowercases all other letters
    // Examples:
    //   lib.capitalize("message_properties") == "Message_properties"
    //   lib.capitalize("message properties") == "Message properties"
    capitalize: function(str) {
      return str.charAt(0).toUpperCase() + str.substring(1).toLowerCase();
    },
    // lib.camelize("message_properties") == "messageProperties"
    // lib.camelize('-moz-border-radius') == 'mozBorderRadius'
    // lib.camelize("message_properties", true) == "MessageProperties"
    camelize: function(str, upper){
      if (upper) { return lib.upperCamelize(str) };
      return str.replace(/[-_]+(.)?/g, function(match, chr) {
        return chr ? chr.toUpperCase() : '';
      });
    },
    // lib.upperCamelize("message_properties") == "MessageProperties"
    upperCamelize: function(str){
      return lib.camelize(lib.capitalize(str));
    },
    // Replaces all spaces or underscores with dashes
    // Examples:
    //   lib.dasherize("message_properties") == "message-properties"
    //   lib.dasherize("Message properties") == "Message-properties"
    dasherize: function(str){
      return str.replace(/\s|_/g, '-');
    },
    // turns number or string formatted number into ordinalize version
    // Examples:
    //   lib.ordinalize(4) == "4th"
    //   lib.ordinalize("13") == "13th"
    //   lib.ordinalize("122") == "122nd"
    ordinalize: function(str){
      var isTeen, r, n;
      n = parseInt(str, 10) % 100;
      isTeen = { 11: true, 12: true, 13: true}[n];
      if(isTeen) {return str + 'th'};
      n = parseInt(str, 10) % 10
      switch(n) {
      case 1:
        r = str + 'st';
        break;
      case 2:
        r = str + 'nd';
        break;
      case 3:
        r = str + 'rd';
        break;
      default:
        r = str + 'th';
      }
      return r;
    },
    pluralize: function(str){
      var uncountable = lib.uncountableWords[str.toLowerCase()];
      if (uncountable) {
        return str;
      };
      var rules = lib.pluralRules;
      for(var i = 0, l = rules.length; i < l; i++){
        if (str.match(rules[i][0])) {
          str = str.replace(rules[i][0], rules[i][1]);
          break;
        };
      }

      return str;
    },
    singularize: function(str){
      var uncountable = lib.uncountableWords[str.toLowerCase()];
      if (uncountable) {
        return str;
      };
      var rules = lib.singularRules;
      for(var i = 0, l = rules.length; i < l; i++){
        if (str.match(rules[i][0])) {
          str = str.replace(rules[i][0], rules[i][1]);
          break;
        };
      }

      return str;
    },
    // Removes leading and trailing whitespace
    // Examples:
    //    lib.strip("    hello world!    ") == "hello world!"
    strip: function(str){
      // implementation from Prototype.js
      return str.replace(/^\s+/, '').replace(/\s+$/, '');
    },
    // Converts a camelized string into a series of words separated by an
    // underscore (`_`).
    // Examples
    //   lib.underscore('borderBottomWidth') == "border_bottom_width"
    //   lib.underscore('border-bottom-width') == "border_bottom_width"
    //   lib.underscore('Foo::Bar') == "foo_bar"
    //   lib.underscore('Foo.Bar') == "foo_bar"
    underscore: function(str){
      // implementation from Prototype.js
      return str.replace(/::/g, '_')
                .replace(/([A-Z]+)([A-Z][a-z])/g, '$1_$2')
                .replace(/([a-z\d])([A-Z])/g, '$1_$2')
                .replace(/[-\.]/g, '_')
                .toLowerCase();
    },
    
    // add an uncountable word
    // fleck.uncountable('ninja', 'tsumani');
    uncountable: function(){
      for(var i=0,l=arguments.length; i<l; i++){
        lib.uncountableWords[arguments[i]] = true;
      }
      return lib;
    }
  };
  
  return lib;
  
});
