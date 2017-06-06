MathJax.Hub.Config({
        extensions: ["tex2jax.js", "TeX/AMSmath.js","TeX/AMSsymbols.js", "cancel"],
        jax: ["input/TeX", "output/HTML-CSS"],
        tex2jax: {
            inlineMath: [ ['$','$'], ["\\(","\\)"] ],
            displayMath: [ ['$$','$$'], ["\\[","\\]"] ],
            processEscapes: true
        },
        "HTML-CSS": { availableFonts: ["TeX"] }
    });
