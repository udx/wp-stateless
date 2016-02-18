/** =========================================================
 * jquery.ud.date_selector.js v0.1.1
 * http://usabilitydynamics.com
 * =========================================================
 *
 * Commercial use requires one-time license fee
 * http://usabilitydynamics.com/licenses
 *
 * Copyright © 2012 Usability Dynamics, Inc. (usabilitydynamics.com)
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL Alexandru Marasteanu BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * TO DO:
 * - Convert all data to be stored in JS object, not in DOM.
 * - Add validation and better converion for date formats to setDate();
 *
 *
 *
 * ========================================================= */

/*jslint devel: true, undef: true, browser: true, continue: true, unparam: true, debug: true, eqeq: true, vars: true, white: true, newcap: true, plusplus: true, maxerr: 50, indent: 2 */
/*global window */
/*global console */
/*global clearTimeout */
/*global setTimeout */
/*global jQuery */ 


(function (jQuery) {
  "use strict";

  jQuery.prototype.date_selector = function ( default_options ) {
  
    var s = {
      element: this,
      cache: {}
    };

    /** Merge Custom Settings with Defaults */
    s.options = jQuery.extend( true, {
      flat: false,
      starts: 1,
      prev: '&#9664;',
      next: '&#9654;',
      lastSel: false,
      mode: 'single',
      view: 'days',
      calendars: 1,
      format: 'Y-m-d',
      position: 'bottom',
      eventName: 'click',
      onRender: function () {
        return {};
      },
      onChange: function () {
        return true;
      },
      onShow: function () {
        return true;
      },
      onBeforeShow: function () {
        return true;
      },
      locale: {
        days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
        daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
        daysMin: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa", "Su"],
        months: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
        monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        weekMin: 'wk'
      },
      views: {
        years: 'date_selector_view_years',
        moths: 'date_selector_view_months',
        days: 'date_selector_view_days'
      },      
      template: {
        wrapper: '<div class="date_selector"><div class="date_selector_borderT" /><div class="date_selector_borderB" /><div class="date_selector_borderL" /><div class="date_selector_borderR" /><div class="date_selector_borderTL" /><div class="date_selector_borderTR" /><div class="date_selector_borderBL" /><div class="date_selector_borderBR" /><div class="date_selector_container"><table cellspacing="0" cellpadding="0"><tbody><tr></tr></tbody></table></div></div>',
        head: [ 
          '<td>', 
          '<table cellspacing="0" cellpadding="0">', 
          '<thead>', 
          '<tr>', 
          '<th class="date_selectorGoPrev"><a href="#"><span><%=prev%></span></a></th>', 
          '<th colspan="6" class="date_selector_month"><a href="#"><span></span></a></th>', 
          '<th class="date_selectorGoNext"><a href="#"><span><%=next%></span></a></th>', 
          '</tr>', 
          '<tr class="date_selector_dow">', 
          '<th><span><%=week%></span></th>', 
          '<th><span><%=day1%></span></th>', 
          '<th><span><%=day2%></span></th>', 
          '<th><span><%=day3%></span></th>', 
          '<th><span><%=day4%></span></th>', 
          '<th><span><%=day5%></span></th>', 
          '<th><span><%=day6%></span></th>', 
          '<th><span><%=day7%></span></th>', 
          '</tr>', 
          '</thead>', 
          '</table></td>'],
        space: '<td class="date_selector_space"><div></div></td>',
        days: ['<tbody class="date_selector_days">', '<tr>', '<th class="date_selectorWeek"><a href="#"><span><%=weeks[0].week%></span></a></th>', '<td class="<%=weeks[0].days[0].classname%>"><a href="#"><span><%=weeks[0].days[0].text%></span></a></td>', '<td class="<%=weeks[0].days[1].classname%>"><a href="#"><span><%=weeks[0].days[1].text%></span></a></td>', '<td class="<%=weeks[0].days[2].classname%>"><a href="#"><span><%=weeks[0].days[2].text%></span></a></td>', '<td class="<%=weeks[0].days[3].classname%>"><a href="#"><span><%=weeks[0].days[3].text%></span></a></td>', '<td class="<%=weeks[0].days[4].classname%>"><a href="#"><span><%=weeks[0].days[4].text%></span></a></td>', '<td class="<%=weeks[0].days[5].classname%>"><a href="#"><span><%=weeks[0].days[5].text%></span></a></td>', '<td class="<%=weeks[0].days[6].classname%>"><a href="#"><span><%=weeks[0].days[6].text%></span></a></td>', '</tr>', '<tr>', '<th class="date_selectorWeek"><a href="#"><span><%=weeks[1].week%></span></a></th>', '<td class="<%=weeks[1].days[0].classname%>"><a href="#"><span><%=weeks[1].days[0].text%></span></a></td>', '<td class="<%=weeks[1].days[1].classname%>"><a href="#"><span><%=weeks[1].days[1].text%></span></a></td>', '<td class="<%=weeks[1].days[2].classname%>"><a href="#"><span><%=weeks[1].days[2].text%></span></a></td>', '<td class="<%=weeks[1].days[3].classname%>"><a href="#"><span><%=weeks[1].days[3].text%></span></a></td>', '<td class="<%=weeks[1].days[4].classname%>"><a href="#"><span><%=weeks[1].days[4].text%></span></a></td>', '<td class="<%=weeks[1].days[5].classname%>"><a href="#"><span><%=weeks[1].days[5].text%></span></a></td>', '<td class="<%=weeks[1].days[6].classname%>"><a href="#"><span><%=weeks[1].days[6].text%></span></a></td>', '</tr>', '<tr>', '<th class="date_selectorWeek"><a href="#"><span><%=weeks[2].week%></span></a></th>', '<td class="<%=weeks[2].days[0].classname%>"><a href="#"><span><%=weeks[2].days[0].text%></span></a></td>', '<td class="<%=weeks[2].days[1].classname%>"><a href="#"><span><%=weeks[2].days[1].text%></span></a></td>', '<td class="<%=weeks[2].days[2].classname%>"><a href="#"><span><%=weeks[2].days[2].text%></span></a></td>', '<td class="<%=weeks[2].days[3].classname%>"><a href="#"><span><%=weeks[2].days[3].text%></span></a></td>', '<td class="<%=weeks[2].days[4].classname%>"><a href="#"><span><%=weeks[2].days[4].text%></span></a></td>', '<td class="<%=weeks[2].days[5].classname%>"><a href="#"><span><%=weeks[2].days[5].text%></span></a></td>', '<td class="<%=weeks[2].days[6].classname%>"><a href="#"><span><%=weeks[2].days[6].text%></span></a></td>', '</tr>', '<tr>', '<th class="date_selectorWeek"><a href="#"><span><%=weeks[3].week%></span></a></th>', '<td class="<%=weeks[3].days[0].classname%>"><a href="#"><span><%=weeks[3].days[0].text%></span></a></td>', '<td class="<%=weeks[3].days[1].classname%>"><a href="#"><span><%=weeks[3].days[1].text%></span></a></td>', '<td class="<%=weeks[3].days[2].classname%>"><a href="#"><span><%=weeks[3].days[2].text%></span></a></td>', '<td class="<%=weeks[3].days[3].classname%>"><a href="#"><span><%=weeks[3].days[3].text%></span></a></td>', '<td class="<%=weeks[3].days[4].classname%>"><a href="#"><span><%=weeks[3].days[4].text%></span></a></td>', '<td class="<%=weeks[3].days[5].classname%>"><a href="#"><span><%=weeks[3].days[5].text%></span></a></td>', '<td class="<%=weeks[3].days[6].classname%>"><a href="#"><span><%=weeks[3].days[6].text%></span></a></td>', '</tr>', '<tr>', '<th class="date_selectorWeek"><a href="#"><span><%=weeks[4].week%></span></a></th>', '<td class="<%=weeks[4].days[0].classname%>"><a href="#"><span><%=weeks[4].days[0].text%></span></a></td>', '<td class="<%=weeks[4].days[1].classname%>"><a href="#"><span><%=weeks[4].days[1].text%></span></a></td>', '<td class="<%=weeks[4].days[2].classname%>"><a href="#"><span><%=weeks[4].days[2].text%></span></a></td>', '<td class="<%=weeks[4].days[3].classname%>"><a href="#"><span><%=weeks[4].days[3].text%></span></a></td>', '<td class="<%=weeks[4].days[4].classname%>"><a href="#"><span><%=weeks[4].days[4].text%></span></a></td>', '<td class="<%=weeks[4].days[5].classname%>"><a href="#"><span><%=weeks[4].days[5].text%></span></a></td>', '<td class="<%=weeks[4].days[6].classname%>"><a href="#"><span><%=weeks[4].days[6].text%></span></a></td>', '</tr>', '<tr>', '<th class="date_selectorWeek"><a href="#"><span><%=weeks[5].week%></span></a></th>', '<td class="<%=weeks[5].days[0].classname%>"><a href="#"><span><%=weeks[5].days[0].text%></span></a></td>', '<td class="<%=weeks[5].days[1].classname%>"><a href="#"><span><%=weeks[5].days[1].text%></span></a></td>', '<td class="<%=weeks[5].days[2].classname%>"><a href="#"><span><%=weeks[5].days[2].text%></span></a></td>', '<td class="<%=weeks[5].days[3].classname%>"><a href="#"><span><%=weeks[5].days[3].text%></span></a></td>', '<td class="<%=weeks[5].days[4].classname%>"><a href="#"><span><%=weeks[5].days[4].text%></span></a></td>', '<td class="<%=weeks[5].days[5].classname%>"><a href="#"><span><%=weeks[5].days[5].text%></span></a></td>', '<td class="<%=weeks[5].days[6].classname%>"><a href="#"><span><%=weeks[5].days[6].text%></span></a></td>', '</tr>', '</tbody>'],
        months: ['<tbody class="<%=className%>">', '<tr>', '<td colspan="2"><a href="#"><span><%=data[0]%></span></a></td>', '<td colspan="2"><a href="#"><span><%=data[1]%></span></a></td>', '<td colspan="2"><a href="#"><span><%=data[2]%></span></a></td>', '<td colspan="2"><a href="#"><span><%=data[3]%></span></a></td>', '</tr>', '<tr>', '<td colspan="2"><a href="#"><span><%=data[4]%></span></a></td>', '<td colspan="2"><a href="#"><span><%=data[5]%></span></a></td>', '<td colspan="2"><a href="#"><span><%=data[6]%></span></a></td>', '<td colspan="2"><a href="#"><span><%=data[7]%></span></a></td>', '</tr>', '<tr>', '<td colspan="2"><a href="#"><span><%=data[8]%></span></a></td>', '<td colspan="2"><a href="#"><span><%=data[9]%></span></a></td>', '<td colspan="2"><a href="#"><span><%=data[10]%></span></a></td>', '<td colspan="2"><a href="#"><span><%=data[11]%></span></a></td>', '</tr>', '</tbody>']      
      }
    }, default_options );


    /**
     * Logging Function.
     *
     * @since 0.1
     * @author potanin@UD
     */
    s.log = typeof s.log === 'function' ? s.log : function (notice, type, console_type, override_debug) {
      //console.log('DP:: ' + notice);
    };


    /**
     * Primary Initializer.
     *
     * @since 0.1
     * @author potanin@UD
     */
    s.initialize = typeof s.initialize === 'function' ? s.initialize : function () {
      s.log('s.initialize()', 'function');
                
      s.extendDate( s.options.locale );    
      s.options.calendars = Math.max(1, parseInt(s.options.calendars, 10) || 1);
      s.options.mode = /single|multiple|range/.test(s.options.mode) ? s.options.mode : 'single';
      
      /* Cycle through each container and identify related calendar elements */
      return s.element.each( function ( index, this_wrapper ) {
        var id = 'date_selector_' + parseInt(Math.random() * 1000), this_calendar, count;

        /* If this element alreay has options saved to data, we do nothing */
        if( jQuery( s.element ).data('date_selector_initialized') ) {
          s.log('s.initialize() - DOM element already initialized, skipping.', 'function');
          return;
        }
        
        if( !s.options.date ) {
          s.options.date = new Date();
        }
                
        if ( s.options.date.constructor === String ) {
          s.options.date = parseDate(s.options.date, s.options.format);
          s.options.date.setHours(0, 0, 0, 0);
        }
        
        if (s.options.mode != 'single') {
          if ( s.options.date.constructor != Array ) {
            s.options.date = [s.options.date.valueOf()];
            if (s.options.mode === 'range') {
              s.options.date.push(((new Date(s.options.date[0])).setHours(23, 59, 59, 0)).valueOf());
            }
          } else {
            for (var i = 0; i < s.options.date.length; i++) {
              s.options.date[i] = (parseDate(s.options.date[i], s.options.format).setHours(0, 0, 0, 0)).valueOf();
            }
            if (s.options.mode === 'range') {
              s.options.date[1] = ((new Date(s.options.date[1])).setHours(23, 59, 59, 0)).valueOf();
            }
          }
        } else {
          s.options.date = s.options.date.valueOf();
        }
        
        if (!s.options.current) {
          s.options.current = new Date();
        } else {
          s.options.current = parseDate(s.options.current, s.options.format);
        }
        
        s.options.current.setDate(1);
        s.options.current.setHours(0, 0, 0, 0);
        
        s.options.id = id;
        
        jQuery( this_wrapper ).data('date_selector_id', s.options.id);
        
        /* Attach to mousedown so propagadtion can be stopped early enough when the calendar is being interacted with */
        this_calendar = jQuery( s.options.template.wrapper ).attr( 'id' , id ).bind( 'mousedown', click ).data('date_selector', s.options);
        
        if (s.options.className) {
          this_calendar.addClass(s.options.className);
        }
        
        var html = '';
        for (var i = 0; i < s.options.calendars; i++) {
          count = s.options.starts;
          
          if (i > 0) {
            html += s.options.template.space;
          }
          
          html += tmpl(s.options.template.head.join(''), {
            week: s.options.locale.weekMin,
            prev: s.options.prev,
            next: s.options.next,
            day1: s.options.locale.daysMin[(count++) % 7],
            day2: s.options.locale.daysMin[(count++) % 7],
            day3: s.options.locale.daysMin[(count++) % 7],
            day4: s.options.locale.daysMin[(count++) % 7],
            day5: s.options.locale.daysMin[(count++) % 7],
            day6: s.options.locale.daysMin[(count++) % 7],
            day7: s.options.locale.daysMin[(count++) % 7]
          });
          
        };
        
        this_calendar.find('tr:first').append( html ).find( 'table' ).addClass( s.options.views[s.options.view] );

        fill( this_calendar.get(0) );

        if ( s.options.flat ) {
          this_calendar.appendTo( this ).show().css('position', 'relative');
          layout(this_calendar.get(0));
          
        } else {
          this_calendar.appendTo(document.body);
          jQuery(this).bind(s.options.eventName, show);
          
        }
        
        jQuery( s.element ).data('date_selector_initialized', true );
      
      });
      
    };
    
    
    /**
     * {}.
     *
     * @since 0.1
     * @author potanin@UD
     */  
    var tmpl = function (str, data) {
      s.log('tmpl()', 'function');
          
      // Figure out if we're getting a template, or if we need to load the template - and be sure to cache the result.
      var fn = !/\W/.test(str) ? s.cache[str] = s.cache[str] || tmpl(document.getElementById(str).innerHTML) :

      // Generate a reusable function that will serve as a template generator (and which will be cached).
      new Function("obj", "var p=[],print=function(){p.push.apply(p,arguments);};" + "with(obj){p.push('" + str.replace(/[\r\t\n]/g, " ").split("<%").join("\t").replace(/((^|%>)[^\t]*)'/g, "$1\r").replace(/\t=(.*?)%>/g, "',$1,'").split("\t").join("');").split("%>").join("p.push('").split("\r").join("\\'") + "');}return p.join('');");
  
      // Provide some basic currying to the user
      return data ? fn(data) : fn;
    };


    /**
     * {}.
     *
     * @since 0.1
     * @author potanin@UD
     */
    var fill = function ( this_calendar ) {
      s.log('fill()', 'function');

      this_calendar = jQuery( this_calendar );
      
      var currentCal = Math.floor( s.options.calendars / 2 ),
        date, data, dow, month, count = 0,
        week, days, indic, indic2, html, tblCal;
        
      this_calendar.find('td>table tbody').remove();
      
      for (var i = 0; i < s.options.calendars; i++) {
        date = new Date(s.options.current);
        date.addMonths(-currentCal + i);
        
        tblCal = this_calendar.find('table').eq( i + 1) ;

        if( typeof tblCal === 'object' && typeof tblCal[0] != 'undefined'  ) {
        
          switch ( tblCal[0].className ) {
            case 'date_selector_view_days':
            dow = s.formatDate(date, 'B, Y');
            break;
            
            case 'date_selector_view_months':
            dow = date.getFullYear();
            break;
            
            case 'date_selector_view_years':
            dow = (date.getFullYear() - 6) + ' - ' + (date.getFullYear() + 5);
            break;
            
          };
        
        }
               
        tblCal.find('thead tr:first th:eq(1) span').text(dow);
        dow = date.getFullYear() - 6;
        data = {
          data: [],
          className: 'date_selector_years'
        }
        
        for (var j = 0; j < 12; j++) {
          data.data.push(dow + j);
        };
        
        html = tmpl(s.options.template.months.join(''), data);
        date.setDate(1);
        data = {
          weeks: [],
          test: 10
        };
        
        month = date.getMonth();
        var dow = (date.getDay() - s.options.starts) % 7;
        date.addDays(-(dow + (dow < 0 ? 7 : 0)));
        week = -1;
        count = 0;
        while (count < 42) {
          indic = parseInt(count / 7, 10);
          indic2 = count % 7;
          if (!data.weeks[indic]) {
            week = date.getWeekNumber();
            data.weeks[indic] = {
              week: week,
              days: []
            };
          }
          data.weeks[indic].days[indic2] = {
            text: date.getDate(),
            classname: []
          };
          if (month != date.getMonth()) {
            data.weeks[indic].days[indic2].classname.push('date_selectorNotInMonth');
          }
          if (date.getDay() === 0) {
            data.weeks[indic].days[indic2].classname.push('date_selectorSunday');
          }
          if (date.getDay() === 6) {
            data.weeks[indic].days[indic2].classname.push('date_selectorSaturday');
          }
          var fromUser = s.options.onRender(date);
          var val = date.valueOf();
          if (fromUser.selected || s.options.date === val || jQuery.inArray(val, s.options.date) > -1 || (s.options.mode === 'range' && val >= s.options.date[0] && val <= s.options.date[1])) {
            data.weeks[indic].days[indic2].classname.push('date_selector_selected');
          }
          if (fromUser.disabled) {
            data.weeks[indic].days[indic2].classname.push('date_selector_disabled');
          }
          if (fromUser.className) {
            data.weeks[indic].days[indic2].classname.push(fromUser.className);
          }
          data.weeks[indic].days[indic2].classname = data.weeks[indic].days[indic2].classname.join(' ');
          count++;
          date.addDays(1);
        }
        
        html = tmpl(s.options.template.days.join(''), data) + html;
        
        data = {
          data: s.options.locale.monthsShort,
          className: 'date_selector_months'
        };
        
        html = tmpl(s.options.template.months.join(''), data) + html;
        tblCal.append(html);
        
      }
    };
    
    
    /**
     * {}.
     *
     * @since 0.1
     * @author potanin@UD
     */    
    var parseDate = function ( date, format ) {
      s.log('parseDate()', 'function');
      
      if( !date ) {
        return;
      }
      
      if ( date.constructor === Date ) {
        return new Date(date);
      }
      var parts = date.split(/\W+/);
      var against = format.split(/\W+/),
        d, m, y, h, min, now = new Date();
      for (var i = 0; i < parts.length; i++) {
        switch (against[i]) {
        case 'd':
        case 'e':
          d = parseInt(parts[i], 10);
          break;
        case 'm':
          m = parseInt(parts[i], 10) - 1;
          break;
        case 'Y':
        case 'y':
          y = parseInt(parts[i], 10);
          y += y > 100 ? 0 : (y < 29 ? 2000 : 1900);
          break;
        case 'H':
        case 'I':
        case 'k':
        case 'l':
          h = parseInt(parts[i], 10);
          break;
        case 'P':
        case 'p':
          if (/pm/i.test(parts[i]) && h < 12) {
            h += 12;
          } else if (/am/i.test(parts[i]) && h >= 12) {
            h -= 12;
          }
          break;
        case 'M':
          min = parseInt(parts[i], 10);
          break;
        }
      }
      return new Date(
      y === undefined ? now.getFullYear() : y, m === undefined ? now.getMonth() : m, d === undefined ? now.getDate() : d, h === undefined ? now.getHours() : h, min === undefined ? now.getMinutes() : min, 0);
    };
    
    
    /**
     * {}.
     *
     * @since 0.1
     * @author potanin@UD
     */    
    s.formatDate = typeof s.formatDate === 'function' ? s.formatDate : function (date, format) {
      
      if( s ) {
        s.log('s.formatDate()', 'function');
      }

      var m = date.getMonth();
      var d = date.getDate();
      var y = date.getFullYear();
      var wn = date.getWeekNumber();
      var w = date.getDay();
      var s = {};
      var hr = date.getHours();
      var pm = (hr >= 12);
      var ir = (pm) ? (hr - 12) : hr;
      var dy = date.getDayOfYear();
      if (ir === 0) {
        ir = 12;
      }
      var min = date.getMinutes();
      var sec = date.getSeconds();
      var parts = format.split(''),
        part;
      for (var i = 0; i < parts.length; i++) {
        part = parts[i];
        switch (parts[i]) {
        case 'a':
          part = date.getDayName();
          break;
        case 'A':
          part = date.getDayName(true);
          break;
        case 'b':
          part = date.getMonthName();
          break;
        case 'B':
          part = date.getMonthName(true);
          break;
        case 'C':
          part = 1 + Math.floor(y / 100);
          break;
        case 'd':
          part = (d < 10) ? ("0" + d) : d;
          break;
        case 'e':
          part = d;
          break;
        case 'H':
          part = (hr < 10) ? ("0" + hr) : hr;
          break;
        case 'I':
          part = (ir < 10) ? ("0" + ir) : ir;
          break;
        case 'j':
          part = (dy < 100) ? ((dy < 10) ? ("00" + dy) : ("0" + dy)) : dy;
          break;
        case 'k':
          part = hr;
          break;
        case 'l':
          part = ir;
          break;
        case 'm':
          part = (m < 9) ? ("0" + (1 + m)) : (1 + m);
          break;
        case 'M':
          part = (min < 10) ? ("0" + min) : min;
          break;
        case 'p':
        case 'P':
          part = pm ? "PM" : "AM";
          break;
        case 's':
          part = Math.floor(date.getTime() / 1000);
          break;
        case 'S':
          part = (sec < 10) ? ("0" + sec) : sec;
          break;
        case 'u':
          part = w + 1;
          break;
        case 'w':
          part = w;
          break;
        case 'y':
          part = ('' + y).substr(2, 2);
          break;
        case 'Y':
          part = y;
          break;
        }
        parts[i] = part;
      }
      return parts.join('');
    };
    
    
            
    /**
     * {}.
     *
     * @since 0.1
     * @author potanin@UD
     */        
    s.extendDate = typeof s.extendDate === 'function' ? s.extendDate : function ( locale ) {
      
      if (Date.prototype.tempDate) {
        return;
      }
      
      Date.prototype.tempDate = null;
      Date.prototype.months = locale.months;
      Date.prototype.monthsShort = locale.monthsShort;
      Date.prototype.days = locale.days;
      Date.prototype.daysShort = locale.daysShort;
      
      Date.prototype.getMonthName = function (fullName) {
        return this[ fullName ? 'months' : 'monthsShort' ][ this.getMonth() ];
      };
      
      Date.prototype.getDayName = function (fullName) {
        return this[fullName ? 'days' : 'daysShort'][this.getDay()];
      };
      
      Date.prototype.addDays = function (n) {
        this.setDate(this.getDate() + n);
        this.tempDate = this.getDate();
      };
      
      Date.prototype.addMonths = function (n) {
        if (this.tempDate === null) {
          this.tempDate = this.getDate();
        }
        this.setDate(1);
        this.setMonth(this.getMonth() + n);
        this.setDate(Math.min(this.tempDate, this.getMaxDays()));
      };
      
      Date.prototype.addYears = function (n) {
        if (this.tempDate === null) {
          this.tempDate = this.getDate();
        }
        this.setDate(1);
        this.setFullYear(this.getFullYear() + n);
        this.setDate(Math.min(this.tempDate, this.getMaxDays()));
      };
      
      Date.prototype.getMaxDays = function () {
        var tmpDate = new Date(Date.parse(this)),
          d = 28,
          m;
        m = tmpDate.getMonth();
        d = 28;
        while (tmpDate.getMonth() === m) {
          d++;
          tmpDate.setDate(d);
        }
        return d - 1;
      };
      
      Date.prototype.getFirstDay = function () {
        var tmpDate = new Date(Date.parse(this));
        tmpDate.setDate(1);
        return tmpDate.getDay();
      };
      
      Date.prototype.getWeekNumber = function () {
        var tempDate = new Date(this);
        tempDate.setDate(tempDate.getDate() - (tempDate.getDay() + 6) % 7 + 3);
        var dms = tempDate.valueOf();
        tempDate.setMonth(0);
        tempDate.setDate(4);
        return Math.round((dms - tempDate.valueOf()) / (604800000)) + 1;
      };
      
      Date.prototype.getDayOfYear = function () {
        var now = new Date(this.getFullYear(), this.getMonth(), this.getDate(), 0, 0, 0);
        var then = new Date(this.getFullYear(), 0, 0, 0, 0, 0);
        var time = now - then;
        return Math.floor(time / 24 * 60 * 60 * 1000);
      };
      
    };
        
            
    /**
     * Displays a single calendar. The calendar object (not the wrapper) is passed to function. 
     *
     * Adds width and height to the Calendar element and to the primary container (.date_selector_container)
     *
     * @since 0.1
     * @author potanin@UD
     */    
    var layout = function ( el ) {
      s.log('layout()', 'function');
    
      var options = jQuery( el ).data('date_selector');
      var this_calendar = jQuery('#' + options.id);

      /* Gets all the divs within the Calendar element */          
      if (!s.options.extraHeight) {
        var divs = jQuery( el ).find( 'div' );
        s.options.extraHeight = divs.get(0).offsetHeight + divs.get(1).offsetHeight;
        s.options.extraWidth = divs.get(2).offsetWidth + divs.get(3).offsetWidth;
      }
      
      var first_table = this_calendar.find('table:first').get(0);
      var width = first_table.offsetWidth;
      var height = first_table.offsetHeight;

      this_calendar.css({
        width: width + s.options.extraWidth + 'px',
        height: height + s.options.extraHeight + 'px'
      }).find('div.date_selector_container').css({
        width: width + 'px',
        height: height + 'px'
      });
      
    };
    
    
    /**
     * Click Monitor for the entire Calendar object.
     *
     * @since 0.1
     * @author potanin@UD
     */    
    var click = function ( ev ) {
      s.log('click()', 'function');
    
      if (jQuery(ev.target).is('span')) {
        ev.target = ev.target.parentNode;
      }
      
      var clicked_element = jQuery(ev.target);
      
      /* If we didn't click a link, there is nothing we can do here */
      if( !clicked_element.is('a') ) {
        return;
      }
      
      ev.stopPropagation();
      
      ev.preventDefault();
      
      ev.target.blur();
      
      if (clicked_element.hasClass('date_selector_disabled')) {
        return false;
      }

		  var options = s.options;
			var parentEl = clicked_element.parent();
			var tblEl = parentEl.parent().parent().parent(); /* the table element, e.g. .date_selector_view_days */
			var tblIndex = jQuery('table', this).index(tblEl.get(0)) - 1;
			var tmp = new Date(options.current);
			var changed = false;
			var fillIt = false;
      
      if (parentEl.is('th')) {
      
        if (parentEl.hasClass('date_selectorWeek') && s.options.mode === 'range' && !parentEl.next().hasClass('date_selector_disabled')) {
          var val = parseInt(parentEl.next().text(), 10);
          tmp.addMonths(tblIndex - Math.floor(s.options.calendars / 2));
          
          if (parentEl.next().hasClass('date_selectorNotInMonth')) {
            tmp.addMonths(val > 15 ? -1 : 1);
          }
          
          tmp.setDate(val);
          s.options.date[0] = (tmp.setHours(0, 0, 0, 0)).valueOf();
          tmp.setHours(23, 59, 59, 0);
          tmp.addDays(6);
          s.options.date[1] = tmp.valueOf();
          fillIt = true;
          changed = true;
          s.options.lastSel = false;
          
        } else if (parentEl.hasClass('date_selector_month')) {
          tmp.addMonths(tblIndex - Math.floor(s.options.calendars / 2));
          switch (tblEl.get(0).className) {
          case 'date_selector_view_days':
            tblEl.get(0).className = 'date_selector_view_months';
            clicked_element.find('span').text(tmp.getFullYear());
            break;
          case 'date_selector_view_months':
            tblEl.get(0).className = 'date_selector_view_years';
            clicked_element.find('span').text((tmp.getFullYear() - 6) + ' - ' + (tmp.getFullYear() + 5));
            break;
          case 'date_selector_view_years':
            tblEl.get(0).className = 'date_selector_view_days';
            clicked_element.find('span').text(s.formatDate(tmp, 'B, Y'));
            break;
          }
          
        } else if (parentEl.parent().parent().is('thead')) {
          switch (tblEl.get(0).className) {
          case 'date_selector_view_days':
            s.options.current.addMonths(parentEl.hasClass('date_selectorGoPrev') ? -1 : 1);
            break;
          case 'date_selector_view_months':
            s.options.current.addYears(parentEl.hasClass('date_selectorGoPrev') ? -1 : 1);
            break;
          case 'date_selector_view_years':
            s.options.current.addYears(parentEl.hasClass('date_selectorGoPrev') ? -12 : 12);
            break;
          }
          fillIt = true;
        }
        
      } else if ( parentEl.is('td') && !parentEl.hasClass('date_selector_disabled')) {
       
        switch (tblEl.get(0).className) {
        
          case 'date_selector_view_months':
            s.options.current.setMonth(tblEl.find('tbody.date_selector_months td').index(parentEl));
            s.options.current.setFullYear(parseInt(tblEl.find('thead th.date_selector_month span').text(), 10));
            s.options.current.addMonths(Math.floor(s.options.calendars / 2) - tblIndex);
            tblEl.get(0).className = 'date_selector_view_days';
          break;
        
          case 'date_selector_view_years':
            s.options.current.setFullYear(parseInt(clicked_element.text(), 10));
            tblEl.get(0).className = 'date_selector_view_months';
          break;
      
          default:
            var val = parseInt(clicked_element.text(), 10);
            tmp.addMonths(tblIndex - Math.floor(s.options.calendars / 2));
            
            if (parentEl.hasClass('date_selectorNotInMonth')) {
              tmp.addMonths(val > 15 ? -1 : 1);
            }
            
            tmp.setDate(val);
            switch (s.options.mode) {
            
              case 'multiple':
                val = (tmp.setHours(0, 0, 0, 0)).valueOf();
                if (jQuery.inArray(val, s.options.date) > -1) {
                  jQuery.each(s.options.date, function (nr, dat) {
                    if (dat === val) {
                      s.options.date.splice(nr, 1);
                      return false;
                    }
                  });
                } else {
                  s.options.date.push(val);
                }
              break;
              
              case 'range':
                if (!s.options.lastSel) {
                  s.options.date[0] = (tmp.setHours(0, 0, 0, 0)).valueOf();
                }
                val = (tmp.setHours(23, 59, 59, 0)).valueOf();
                if (val < s.options.date[0]) {
                  s.options.date[1] = s.options.date[0] + 86399000;
                  s.options.date[0] = val - 86399000;
                } else {
                  s.options.date[1] = val;
                }
                s.options.lastSel = !s.options.lastSel;
              break;
              
              default:
                s.options.date = tmp.valueOf();
              break;
            
            }
            
          break;
        }
        
        fillIt = true;
        changed = true;
        
      }
      
      if (fillIt) {
        fill(this);
      }
      
      if (changed) {
        s.options.onChange.apply( this, prepareDate( options ) );
      }

    };
    
    
    /**
     * {}.
     *
     * @since 0.1
     * @author potanin@UD
     */    
    var prepareDate = function ( options ) {
      s.log('prepareDate()', 'function');
    
      var tmp;
      
      if (s.options.mode === 'single') {
        tmp = new Date( s.options.date );
        return [s.formatDate( tmp, s.options.format), tmp, s.options.el];
        
      } else {
      
        tmp = [ [], [], s.options.el ];
          
        jQuery.each( s.options.date , function( nr, val ) {
          var date = new Date(val);
          tmp[0].push(s.formatDate( date , s.options.format));
          tmp[1].push(date);
        });
        
        return tmp;
      }
    };
    
    
    /**
     * {}.
     *
     * @since 0.1
     * @author potanin@UD
     */    
    var getViewport = function () {
      s.log('getViewport()', 'function');
    
      var m = document.compatMode === 'CSS1Compat';
      return {
        l: window.pageXOffset || (m ? document.documentElement.scrollLeft : document.body.scrollLeft),
        t: window.pageYOffset || (m ? document.documentElement.scrollTop : document.body.scrollTop),
        w: window.innerWidth || (m ? document.documentElement.clientWidth : document.body.clientWidth),
        h: window.innerHeight || (m ? document.documentElement.clientHeight : document.body.clientHeight)
      };
    };
    
    
    /**
     * {}.
     *
     * @since 0.1
     * @author potanin@UD
     */    
    var isChildOf = function (parentEl, el, container) {
      s.log('isChildOf()', 'function');
    
      if (parentEl === el) {
        return true;
      }
      if (parentEl.contains) {
        return parentEl.contains(el);
      }
      if (parentEl.compareDocumentPosition) {
        return !!(parentEl.compareDocumentPosition(el) & 16);
      }
      var prEl = s.element.parentNode;
      while (prEl && prEl != container) {
        if (prEl === parentEl) return true;
        prEl = prEl.parentNode;
      }
      return false;
    }
    
    
    /**
     * {}.
     *
     * @since 0.1
     * @author potanin@UD
     */    
    var show = function ( ev ) {
      s.log('show()', 'function');
      
      /* Get the calendar container DOM element */
      var element = this;
    
      /* Get jQuery Object for calendar */
      var calendar_object = jQuery('#' + jQuery( this ).data('date_selector_id'));
      
      if (!calendar_object.is(':visible')) {
      
        /* Get DOM Element */
        var calendar_element = calendar_object.get(0);
        
        fill( calendar_element );
        
        /* Applying to the wrapper? */
        s.options.onBeforeShow.apply(this, [ calendar_element ]);
        
        var pos = jQuery( element ).offset();
        var viewPort = getViewport();
        var top = pos.top;
        var left = pos.left;
        var oldDisplay = jQuery.curCSS( calendar_element , 'display' );
        
        calendar_object.css({
          visibility: 'hidden',
          display: 'block'
        });
        
        layout( calendar_element );
        
        switch (s.options.position) {
          case 'top':
            top -= calendar_element.offsetHeight;
          break;
          case 'left':
            left -= c.offsetWidth;
          break;
          case 'right':
            left += element.offsetWidth;
          break;
          case 'bottom':
            top += element.offsetHeight;
          break;
        }
        
        if (top + calendar_element.offsetHeight > viewPort.t + viewPort.h) {
          top = pos.top - calendar_element.offsetHeight;
        }
        
        if (top < viewPort.t) {
          top = pos.top + this.offsetHeight + calendar_element.offsetHeight;
        }
        
        if (left + calendar_element.offsetWidth > viewPort.l + viewPort.w) {
          left = pos.left - calendar_element.offsetWidth;
        }
        
        if (left < viewPort.l) {
          left = pos.left + this.offsetWidth
        }
        
        calendar_object.css({
          visibility: 'visible',
          display: 'block',
          top: top + 'px',
          left: left + 'px'
        });
        
        if (s.options.onShow.apply(this, [ calendar_element  ]) != false) {
          calendar_object.show();
        }
        
        jQuery( document ).bind( 'mousedown' , {
          element: element,
          calendar_object: calendar_object,
          trigger: this
        }, hide );
        
      }
      return false;
    };
    
    
    /**
     * {}.
     *
     * @since 0.1
     * @author potanin@UD
     */    
    var hide = function ( ev, args ) {
      s.log('hide()', 'function');
    
      if ( ev.target != ev.data.trigger && !isChildOf( ev.data.calendar_object, ev.target, ev.data.element )) {
        ev.data.calendar_object.hide();
        jQuery(document).unbind('mousedown', hide);
      }
    };



    /**
     * {}
     *
     * @author potanin@UD
     */
    this.showPicker = s.showPicker = typeof s.showPicker === 'function' ? s.showPicker : function() {    
      s.log('showPicker()', 'function');
    
      return this.each(function () {
        if (jQuery(this).data('date_selector_id')) {
          show.apply(this);
        }
      });
    };
    
    
    /**
     * {}
     *
     * @author potanin@UD
     */
    this.hidePicker = s.hidePicker = typeof s.hidePicker === 'function' ? s.hidePicker : function() {      
      s.log('hidePicker()', 'function');
    
      return this.each(function () {
        if (jQuery(this).data('date_selector_id')) {
          jQuery('#' + jQuery(this).data('date_selector_id')).hide();
        }
      });
    };
    
    
    /**
     * {}
     *
     * 
     * @author potanin@UD
     */
    this.setDate = s.setDate = typeof s.setDate === 'function' ? s.setDate : function( date, shiftTo ) {      
      s.log('setDate()', 'function');

      return s.element.each( function () {
      
        if( !jQuery( this ).data('date_selector_id')) {
          s.log('setDate() - Element not initialized.', 'function');    
          return;
        }
      
        var cal = jQuery('#' + jQuery(this).data('date_selector_id'));
        
        s.options.date = date;
                
        if( !s.options.date ) {
          s.options.date = new Date();
        }
                
        if ( s.options.date.constructor === String ) {
          s.options.date = parseDate(s.options.date, s.options.format);
          s.options.date.setHours(0, 0, 0, 0);
        }
        
        if (s.options.mode != 'single') {
          if ( s.options.date.constructor != Array ) {
            s.options.date = [s.options.date.valueOf()];
            if (s.options.mode === 'range') {
              s.options.date.push(((new Date(s.options.date[0])).setHours(23, 59, 59, 0)).valueOf());
            }
          } else {
          
            for (var i = 0; i < s.options.date.length; i++) {
              if( typeof s.options.date[i] != 'undefined' ) {
                s.options.date[i] = (parseDate( s.options.date[i] , s.options.format).setHours(0, 0, 0, 0)).valueOf();
              }
            }
            
            if ( s.options.mode === 'range' ) {
              s.options.date[1] = ((new Date(s.options.date[1])).setHours(23, 59, 59, 0)).valueOf();
            }
          }
        } else {
          s.options.date = s.options.date.valueOf();
        }
                
        
        if (shiftTo) {
          s.options.current = new Date( s.options.mode != 'single' ? s.options.date[0] : s.options.date );
        }

        fill( s.element.get(0) );
        
      });
      
    };
    
    
    /**
     * {}
     *
     * @author potanin@UD
     */
    this.getDate = s.getDate = typeof s.getDate === 'function' ? s.getDate : function() {      
      s.log('getDate()', 'function');

      if (this.size() > 0) {
        return prepareDate(jQuery('#' + jQuery(this).data('date_selector_id')).data('date_selector'))[formated ? 0 : 1];
      }
    };
    
    
    /**
     * {}
     *
     * @author potanin@UD
     */
    this.Clear = s.clear = typeof s.clear === 'function' ? s.clear : function() {      
      s.log('clear()', 'function');
    
      return this.each(function () {
        if (jQuery(this).data('date_selector_id')) {
          var cal = jQuery('#' + jQuery(this).data('date_selector_id'));
          s.options = s.element.data('date_selector');
          if (s.options.mode != 'single') {
            s.options.date = [];
            fill(s.element.get(0));
          }
        }
      });
    };
    
    
    /**
     * {}
     *
     * @author potanin@UD
     */
    this.fixLayout = s.fixLayout = typeof s.fixLayout === 'function' ? s.fixLayout : function() {      
      s.log('fixLayout()', 'function');
    
      return this.each(function () {
        if (jQuery(this).data('date_selector_id')) {
          var cal = jQuery('#' + jQuery(this).data('date_selector_id'));
          s.options = s.element.data('date_selector');
          if (s.options.flat) {
            layout(s.element.get(0));
          }
        }
      });
    };
    

    s.initialize();
    
    return this;

  } /* end jQuery.prototype.date_selector */

}(jQuery) /* p.s. No dog balls. */ );


