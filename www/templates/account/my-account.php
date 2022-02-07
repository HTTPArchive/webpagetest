<script>
((window) => {
class Modal extends HTMLElement {
  constructor(){
    super();
    this._init = this._init.bind(this);
      this._observer = new MutationObserver(this._init);
  }
  connectedCallback(){
    if (this.children.length) {
      this._init();
    }
    this._observer.observe(this, { childList: true });
  }
  makeEvent( evtName ){
    if( typeof window.CustomEvent === "function" ){
      return new CustomEvent( evtName, {
        bubbles: true,
        cancelable: false
      });
    } else {
      var evt = document.createEvent('CustomEvent');
      evt.initCustomEvent( evtName, true, true, {} );
      return evt;
    }
  }
  _init(){
    this.closetext = "Close dialog";
    this.closeclass = "modal_close";
    this.closed = true;

    this.initEvent = this.makeEvent( "init" );
    this.beforeOpenEvent = this.makeEvent( "beforeopen" );
    this.openEvent = this.makeEvent( "open" );
    this.closeEvent = this.makeEvent( "close" );
    this.beforeCloseEvent = this.makeEvent( "beforeclose" );
    this.activeElem = document.activeElement;
    this.closeBtn = this.querySelector( "." + this.closeclass ) || this.appendCloseBtn();
    this.titleElem = this.querySelector( ".modal_title" );
    this.enhanceMarkup();
    this.bindEvents();
    this.dispatchEvent( this.initEvent );
  }
  closest(el, s){		
    var whichMatches = Element.prototype.matches || Element.prototype.msMatchesSelector;
      do {
        if (whichMatches.call(el, s)) return el;
        el = el.parentElement || el.parentNode;
      } while (el !== null && el.nodeType === 1);
      return null;
  }
  appendCloseBtn(){
    var btn = document.createElement( "button" );
    btn.className = this.closeclass;
    btn.innerHTML = this.closetext;
    this.appendChild(btn);
    return btn;
  }

  enhanceMarkup(){
    this.setAttribute( "role", "dialog" );
    this.id = this.id || ("modal_" + new Date().getTime());
    if( this.titleElem ){
      this.titleElem.id = this.titleElem.id || ("modal_title_" + new Date().getTime());
      this.setAttribute( "aria-labelledby", this.titleElem.id );
    }
    this.classList.add("modal");
    this.setAttribute("tabindex","-1");
    this.overlay = document.createElement("div");
    this.overlay.className = "modal_screen";
    this.parentNode.insertBefore(this.overlay, this.nextSibling);
    this.modalLinks = "a.modal_link[href='#" + this.id + "']";
    this.changeAssocLinkRoles();
  }

  addInert(){
    var self = this;
    function inertSiblings( node ){
      if( node.parentNode ){
        for(var i in node.parentNode.childNodes ){
          var elem = node.parentNode.childNodes[i];
          if( elem !== node && elem.nodeType === 1 && elem !== self.overlay ){
            elem.inert = true;
          }
        }
        if( node.parentNode !== document.body ){
          inertSiblings(node.parentNode);
        }
      }

    }
    inertSiblings(this);
  }

  removeInert(){
    var elems = document.querySelectorAll( "[inert]" );
    for( var i = 0; i < elems.length; i++ ){
      elems[i].inert = false;
    }
  }

  open( programmedOpen ){
    this.dispatchEvent( this.beforeOpenEvent );
    this.classList.add( "modal-open" );
    if( !programmedOpen ){
      this.focusedElem = this.activeElem;
    }
    this.closed = false;
    this.focus();
    this.addInert();
    this.dispatchEvent( this.openEvent );
  }



  close( programmedClose ){
    var self = this;
    this.dispatchEvent( this.beforeCloseEvent );
    this.classList.remove( "modal-open" );
    this.closed = true;
    self.removeInert();
    var focusedElemModal = self.closest(this.focusedElem, ".modal");
    if( focusedElemModal ){
      focusedElemModal.open( true );
    }
    if( !programmedClose ){
      this.focusedElem.focus();
    }

    this.dispatchEvent( this.closeEvent );
  }

  changeAssocLinkRoles(){
    var elems = document.querySelectorAll(this.modalLinks);
    for( var i = 0; i < elems.length; i++ ){
      elems[i].setAttribute("role", "button" );
    }
  }


  bindEvents(){
    var self = this;

    // close btn click
    this.closeBtn.addEventListener('click', event => self.close());

    // open dialog if click is on link to dialog
    window.addEventListener('click', function( e ){
      var assocLink = self.closest(e.target, self.modalLinks);
      if( assocLink ){
        e.preventDefault();
        self.open();
      }
    });

    window.addEventListener('keydown', function( e ){
      var assocLink = self.closest(e.target, self.modalLinks);
      if( assocLink && e.keyCode === 32 ){
        e.preventDefault();
        self.open();
      }
    });

    window.addEventListener('focusin', function( e ){
      self.activeElem = e.target;
    });

    // click on the screen itself closes it
    this.overlay.addEventListener('mouseup', function( e ){
      if( !self.closed ){
        self.close();
      }
    });

    // click on anything outside dialog closes it too (if screen is not shown maybe?)
    window.addEventListener('mouseup', function( e ){
      if( !self.closed && !self.closest(e.target, "#" + self.id ) ){
        e.preventDefault();
        self.close();
      }
    });


    // close on escape
    window.addEventListener('keydown', function( e){
      if( e.keyCode === 27 &&  !self.closed ){
        e.preventDefault();
        self.close();
      }

    });

    // close on other dialog open
    window.addEventListener('beforeopen', function( e){
      if( !self.closed && e.target !== this ){
        self.close( true );
      }
    });
  }

  disconnectedCallback(){
    this._observer.disconnect();
    // remove screen when elem is removed
    this.overlay.remove();
  }
}

if ('customElements' in window) {
  customElements.define('fg-modal', Modal );
}

window.Modal = Modal;

})(window);

</script>
<div class="my-account-page">
  <div class="subhed">
    <h1>My Account</h1>
    <?php if ($is_paid) { ?>
    <div class="contact-support-button">
      <a href="https://support.webpagetest.org"><span>Contact Support</span></a>
    </div>
    <?php } ?>
  </div>

  <div>
    <div class="card contact-info" data-modal="contact-info-modal">
      <div class="card-section">
        <h3><?php echo htmlspecialchars($first_name) . ' ' . htmlspecialchars($last_name); ?></h3>
        <div class="info">
          <div><?php echo htmlspecialchars($email); ?></div>
        </div>
      </div>
      <div class="card-section">
        <div class="edit-button">
          <button><span>Edit</span></button>
        </div>
      </div>
    </div>

    <div class="card password" data-modal="password-modal">
      <div class="card-section">
        <h3>Password</h3>
        <div class="info">
          <div>************</div>
        </div>
      </div>
      <div class="card-section">
        <div class="edit-button">
          <button><span>Edit</span></button>
        </div>
      </div>
    </div>

<?php if ($is_paid) {
  include_once __DIR__ . '/includes/signup.php';
} else {
  include_once __DIR__ . '/includes/billing-data.php';
} ?>
</div>


<fg-modal id="contact-info-modal" class="contact-info-modal fg-modal">
  <form method="POST" action="/oauth2/account.php">
    <fieldset>
      <legend class="modal_title">Contact Information</legend>
      <div class="input-set">
        <div class="section">
          <div>
            <label class="required" for="first-name">First Name</label>
            <input type="text" name="first-name" maxlength=32 pattern="<?php echo $validation_pattern; ?>" value="<?php echo htmlspecialchars($first_name); ?>" title="Do not use <, >, or &#" required />
          </div>
          <div>
            <label class="required" for="last-name">Last Name</label>
            <input type="text" name="last-name" maxlength=32 pattern="<?php echo $validation_pattern; ?>" title="Do not use <, >, or &#" value="<?php echo htmlspecialchars($last_name); ?>" required />
          </div>
        </div>
        <div class="section">
          <label for="company-name">Company Name</label>
          <input type="text" name="company-name" maxlength=32 title="Do not use <, >, or &#" value="<?php echo htmlspecialchars($company_name); ?>" />
<!--          <input type="text" name="company-name" pattern="<?php echo $validation_pattern; ?>" title="Do not use <, >, or &#" value="<?php echo htmlspecialchars($company_name); ?>" />
-->
        </div>
        <div class="section">
          <label class="required disabled" for="email">Email</label>
          <input type="email" name="email" disabled required value="<?php echo htmlspecialchars($email); ?>" />
        </div>
        <input type="hidden" name="id" value="<?php echo $id; ?>" />
        <input type="hidden" name="type" value="contact_info" />
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token ?>" />
        <div class="save-button">
          <button type="submit">Save</button>
        </div>
      </div>
    </fieldset>
  </form>
</fg-modal>


<fg-modal id="password-modal" class="password-modal fg-modal">
  <form method="POST" action="/oauth2/account.php">
    <fieldset>
      <legend class="modal_title">Change your password</legend>
      <p class="details">The requirements are at least 8 characters, including a number, lowercase letter, uppercase letter and symbol. No &lt;, &gt;.</p>
      <div class="input-set">
        <div class="section">
          <label class="required" for="current-password">Current Password</label>
          <input type="text" name="current-password" required />
        </div>
        <div class="section">
          <label class="required" for="new-password">New Password</label>
          <input type="text" name="new-password" required />
        </div>
        <div class="section">
          <label class="required" for="confirm-new-password">New Password</label>
          <input type="text" name="confirm-new-password" required />
        </div>
        <div class="section">
          <div class="save-button">
            <button type="submit">Save</button>
          </div>
          <div class="cancel-button">
            <button>Cancel</button>
          </div>
        </div>
      </div>
    </fieldset>
  </form>
</fg-modal>

<fg-modal id="subscription-plan-modal" class="subscription-plan-modal fg-modal">
  <form method="POST" action="/oauth2/account.php">
    <fieldset>
      <legend class="modal_title">Subscription Details</legend>
    </fieldset>
  </form>
</fg-modal>

<fg-modal id="subscription-plan-modal" class="subscription-plan-modal fg-modal">
  <form method="POST" action="/oauth2/account.php">
    <fieldset>
      <legend class="modal_title">Subscription Details</legend>
    </fieldset>
  </form>
</fg-modal>

<fg-modal id="payment-info-modal" class="payment-info-modal fg-modal">
  <form method="POST" action="/oauth2/account.php">
    <fieldset>
      <legend class="modal_title">Payment Information</legend>
    </fieldset>
  </form>
</fg-modal>

<script>
(() => {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.edit-button button').forEach(el => {
        el.addEventListener('click', (e) => {
          const card = e.target.closest('[data-modal]');
          const modal = card.dataset.modal;
          document.querySelector(`#${modal}`).open();
        });
      });
    });
  } else {
    document.querySelectorAll('.edit-button button').forEach(el => {
      el.addEventListener('click', (e) => {
        const card = e.target.closest('[data-modal]');
        const modal = card.dataset.modal;
        document.querySelector(`#${modal}`).open();
      });
    });
  }
})();
</script>
