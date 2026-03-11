// Ce fichier sert à initialiser Stimulus automatiquement.
import "./stimulus_bootstrap.js";

// Import Bootstrap's JavaScript and CSS
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap";
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */

// import css
import "./styles/app.css";
import "./js/surveillance.js";
import { fetchJSON } from "./js/functions.js";

// js pour faire defiler la devise  sur la page d'accueil
document.addEventListener("DOMContentLoaded", function () {
    const marquee = document.querySelector(".marquee");
    if (!marquee) return;

    const inner = marquee.querySelector(".marquee__inner");
    // Ensure there are at least two copies for smooth loop
    if (inner.children.length === 1) {
        const clone = inner.children[0].cloneNode(true);
        inner.appendChild(clone);
    }

    let speed = 80; // pixels per second
    let pos = 0;
    let rafId = null;
    let lastTs = null;

    function step(ts) {
        if (!lastTs) lastTs = ts;
        const dt = (ts - lastTs) / 1000;
        lastTs = ts;

        pos -= speed * dt;
        const resetAt = inner.scrollWidth / 2;
        if (Math.abs(pos) >= resetAt) {
            pos = 0;
        }
        inner.style.transform = `translateX(${pos}px)`;
        rafId = requestAnimationFrame(step);
    }

    function start() {
        cancelAnimationFrame(rafId);
        lastTs = null;
        rafId = requestAnimationFrame(step);
    }

    function onResize() {
        // small debounce effect: reset position so layout recalculates
        pos = 0;
    }

    window.addEventListener("resize", onResize);
    start();
});

const enseigneElement = fetchJSON("/enseignement");
enseigneElement.then((data) => {
    data.forEach((item) => {
        console.log(item.teacher.lastname);
    });
});
