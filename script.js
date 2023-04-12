console.clear();

const inputContainer = document.querySelector(".input-wrapper");
const textInput = document.querySelector("input#protocol");
const textSelect = document.querySelector("select");
const svgEnterIcon = document.querySelector(".icon.enter-key");
const form = document.querySelector("#form");
const loader = document.querySelector(".loader");

const ENTER_KEYCODE = 13;
const TAB_KEYCODE = 9;
const BACKSPACE_KEYCODE = 8;
const UP_ARROW_KEYCODE = 38;
const DOWN_ARROW_KEYCODE = 40;
const SPACE_KEYCODE = 32;

let insertText = false;

textInput.addEventListener("input", e => {
	if (e.data != " ") {
		insertText = true;
	}
	if (insertText == false) {
		textInput.value = "";
	}
	
	textInput.value = textInput.value.replace(/[^0-9]/g, '');

	let inputValue = e.target.value;

	if (inputValue.length < 6) {
		svgEnterIcon.classList.add("hidden");
	} else {
	    svgEnterIcon.classList.remove("hidden");
	}

	if (textInput.value.length == 0) {
		insertText = false;
	}
});

function foo()
{
    form.classList.add("hidden");
    loader.classList.remove("hidden");
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "/xml");
    xhr.responseType = 'arraybuffer';
    var formData = new FormData();
    formData.append('protocol', textInput.value);
    formData.append("metrologist_id", textSelect.selectedIndex);
    xhr.send(formData);
    
    xhr.onerror = function() {
        loader.classList.add("hidden");
        form.classList.remove("hidden");
        alert(`Ошибка соединения`);
    };
    
    xhr.onload = function () {
        loader.classList.add("hidden");
        form.classList.remove("hidden");
        if (this.status === 200) {
            var filename = "";
            var disposition = xhr.getResponseHeader('Content-Disposition');
            if (disposition && disposition.indexOf('attachment') !== -1) {
                var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                var matches = filenameRegex.exec(disposition);
                if (matches != null && matches[1]) filename = matches[1].replace(/['"]/g, '');
            }
            var type = xhr.getResponseHeader('Content-Type');

            var blob = new Blob([this.response], { type: type });
            if (typeof window.navigator.msSaveBlob !== 'undefined') {
                // IE workaround for "HTML7007: One or more blob URLs were revoked by closing the blob for which they were created. These URLs will no longer resolve as the data backing the URL has been freed."
                window.navigator.msSaveBlob(blob, filename);
            } else {
                var URL = window.URL || window.webkitURL;
                var downloadUrl = URL.createObjectURL(blob);

                if (filename) {
                    // use HTML5 a[download] attribute to specify filename
                    var a = document.createElement("a");
                    // safari doesn't support this yet
                    if (typeof a.download === 'undefined') {
                        window.location = downloadUrl;
                    } else {
                        a.href = downloadUrl;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                    }
                } else {
                    window.location = downloadUrl;
                }

                setTimeout(function () { URL.revokeObjectURL(downloadUrl); }, 100); // cleanup
            }
        } else {
            alert('Неверный номер протокола');
        }
    }
}

textInput.addEventListener("keydown", e => {
	if (e.keyCode == ENTER_KEYCODE) {
		if (textInput.value.length < 6) return;
		foo();
	}
});

svgEnterIcon.addEventListener('click', () => {
    foo();
})

removeClassAfterAnimationCompletes(inputContainer, "animate");

function removeClassAfterAnimationCompletes(el, className) {
	let elStyles = window.getComputedStyle(inputContainer);
	setTimeout(function() {
		el.classList.remove(className);
	}, +elStyles.animationDuration.replace("s", "") * 1000);
}

const helpButton = document.getElementById("help");
helpButton.addEventListener('click', (e) => {
	window.open("/help",'_blank');
});