class BookmarkUrlInputElement extends HTMLInputElement {
	constructor() {
    	super();
    	this.matchingBookmarks = [];
	}

	connectedCallback() {
		this.render();
		this.addEventListener('change', this.handleInput.bind(this));
	}

	async handleInput() {
		const response = await this.getMatchingBookmarks();
		this.render();
  	}
  	
	async getMatchingBookmarks() {
    	try {
    	    const urlPattern = /^(https?:\/\/)?([\w-]+(\.[\w-]+)+)(:\d{2,5})?(\/.*)?$/i;
    	    
      		if (urlPattern.test(this.value)) {
		        if (typeof apiUrl === "undefined") {
		          throw new Error('apiUrl is not defined');
		        }
	            const response = await fetch(`${apiUrl}/bookmarks/index.php?url=${encodeURIComponent(this.value)}`);
		        if (!response.ok) {
		          throw new Error('Failed request');
		        }
		        const result = await response.json();
	        	this.matchingBookmarks = result.Collection || [];
	        	return result;
	      	} else {
	      		this.matchingBookmarks = [];
	       		return [];
	      	}
	    } catch (error) {
	      console.error(error);
	      return [];
		}
	}

	render() {
		// Remove existing elements before adding new ones
		const existingAlert = this.nextElementSibling;
		
		if (existingAlert && existingAlert.classList.contains('alert')) {
	        existingAlert.remove();
		}
		
		if (this.matchingBookmarks.length > 0) {
			const ul = document.createElement('ul');
			this.matchingBookmarks.forEach(b => {
		      const li = document.createElement('li');
		      li.textContent = b.url; // Prevents XSS
		      ul.appendChild(li);
		    });
	
		    const div = document.createElement('div');
			div.classList.add('alert', 'alert-primary');
			div.innerHTML = '<small>Déjà enregistré</small>';
			div.appendChild(ul);
	
		    this.insertAdjacentElement("afterend", div);
	    }
	}
}
