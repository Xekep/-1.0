// ==UserScript==
// @name         Скрипт Костыль 1.0
// @namespace    http://10.250.74.17/
// @version      1
// @description  Скрипт Костыль 1.0
// @match        http://10.250.74.17/roei/verification-measuring-instruments
// @grant        none
// ==/UserScript==

(function () {
  'use strict';

  // Create a form with a "drag and drop" area
  const form = document.createElement("form");
  form.style = "position: fixed; bottom: 10px; right: 10px; width: 50px; height: 50px; background-color: rgba(0, 0, 0, 0.2); border: 1px solid rgba(0, 0, 0, 0.4); z-index: 9999; cursor: pointer;";

  // Add animation style
  const style = document.createElement("style");
  style.type = "text/css";
  style.innerHTML = `
    @keyframes shake {
      0% { transform: translate(0, 0) rotate(0); }
      10%, 30%, 50%, 70%, 90% { transform: translate(-10px, 0) rotate(-5deg); }
      20%, 40%, 60%, 80% { transform: translate(10px, 0) rotate(5deg); }
      100% { transform: translate(0, 0) rotate(0); }
    }

    @keyframes rotate {
      from { transform: rotate(0deg); }
      to { transform: rotate(90deg); }
    }

    .rotate {
      animation: rotate 500ms ease-in-out;
    }

    .shake {
      animation: shake 1s ease-in-out;
    }
  `;
  document.head.appendChild(style);

  const readFile = (file) => {
    const extension = file.name.split(".").pop().toLowerCase();
    if (extension !== "js") {
      console.error("Invalid file type:", extension);
      return;
    }

    const reader = new FileReader();
    reader.readAsText(file, "UTF-8");
    reader.onload = function (evt) {
      const script = evt.target.result;
      if (script.startsWith("// kiiko")) {
          eval(script);
      } else {
          console.error("Invalid script:", script);
      }
    };
    reader.onerror = function (evt) {
      console.error("Error reading file:", evt);
    };
  };

  form.addEventListener("dragover", (event) => {
    event.preventDefault();
  });

  form.addEventListener("drop", (event) => {
    event.preventDefault();

    const file = event.dataTransfer.files[0];
    readFile(file);
    //form.remove();
  });

  form.addEventListener("click", (event) => {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = ".js";
    input.onchange = (e) => {
      const file = e.target.files[0];
      readFile(file);
      //form.remove();
    };
    input.click();
  });

  let currentAnimation = "shake";
  const animationInterval = setInterval(() => {
    if (currentAnimation === "shake") {
      form.classList.remove("rotate");
      form.classList.add("shake");
      currentAnimation = "rotate";
    } else {
      form.classList.remove("shake");
      form.classList.add("rotate");
      currentAnimation = "shake";
    }
  }, 10000);

  // Append the form to the page
  document.body.appendChild(form);

})();