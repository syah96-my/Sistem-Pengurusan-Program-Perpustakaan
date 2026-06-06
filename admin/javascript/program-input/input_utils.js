/* ============================================================
   GLOBAL JS UTILITIES
   SAFE - NO SIDE EFFECTS
============================================================ */

"use strict";

window.Utils = window.Utils || {};

/* ============================================================
   LOGGING HELPERS
============================================================ */

Utils.log = function (...args) {
    if (window.APP?.DEBUG) {
        console.log("[APP]", ...args);
    }
};

Utils.error = function (...args) {
    console.error("[APP]", ...args);
};

/* ============================================================
   DATE HELPERS
============================================================ */

/**
 * Convert DD/MM/YYYY or DD/MM/YYYY HH:MM
 * -> YYYY-MM-DD HH:MM:00
 */
Utils.convertDate = function (value) {
    if (!value) return null;

    const regex = /^(\d{1,2})\/(\d{1,2})\/(\d{4})(?:\s+(\d{1,2}):(\d{2}))?$/;
    const m = value.trim().match(regex);
    if (!m) return null;

    let [, dd, mm, yyyy, HH = "00", MM = "00"] = m;

    return (
        yyyy + "-" +
        mm.padStart(2, "0") + "-" +
        dd.padStart(2, "0") + " " +
        HH.padStart(2, "0") + ":" +
        MM.padStart(2, "0") + ":00"
    );
};

Utils.isValidDate = function (value) {
    return Utils.convertDate(value) !== null;
};

/* ============================================================
   CSV PARSER (SIMPLE, NO QUOTES SUPPORT - SAME AS ORIGINAL)
============================================================ */

Utils.parseCSV = function (text) {
    const lines = text.trim().split("\n");
    const header = lines[0].split(",").map(h => h.trim());

    return lines.slice(1).map((line, idx) => {
        const cols = line.split(",");
        let row = {};
        header.forEach((h, i) => {
            row[h] = (cols[i] || "").trim();
        });
        row.__rowIndex = idx + 2;
        return row;
    });
};

/* ============================================================
   HASH HELPER (USED BY PARTICIPANT LINK)
============================================================ */

Utils.hashId = async function (id) {
    const encoder = new TextEncoder();
    const data = encoder.encode(String(id));
    const hashBuffer = await crypto.subtle.digest("SHA-256", data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map(b => b.toString(16).padStart(2, "0")).join("");
};



/* ============================================================
   TOOGLE FIELD
============================================================ */
function toggleModeFields(mode) {
    $(".url-field").show();

    if (mode === "physical") {
        $(".online-field").hide();
        $(".physical-field").show();
    }
    else if (mode === "online") {
        $(".online-field").show();
        $(".physical-field").hide();
    }
    else { // hybrid
        $(".online-field").show();
        $(".physical-field").show();
    }
}

/* ============================================================
   URL HELPERS
============================================================ */
Utils.isValidURL = function (value) {
    if (!value) return true;

    try {
        const url = new URL(value);
        return ["http:", "https:"].includes(url.protocol);
    } catch (e) {
        return false;
    }
};

/* ============================================================
   END UTILS
============================================================ */
