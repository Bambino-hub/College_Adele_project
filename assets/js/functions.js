// cette fonction permet de faire une requete fetch et de retourner le résultat au format json
export async function fetchJSON(url, options = {}) {
    const headers = {
        Accept: "application/json",
        ...options.headers,
    };

    const response = await fetch(url, {
        ...options,
        headers,
    });

    if (!response.ok) {
        throw new Error("Erreur serveur");
    }

    return await response.json();
}

/**
 *ccette fonction permet de créer un élément HTML avec des attributs
 * @param {string} tagName
 * @param {Object} Attribute
 * @return {HTMLElement}
 */
export function createElementWithAtribute(tagName, Attribute = {}) {
    const element = document.createElement(tagName);
    for (const [key, value] of Object.entries(Attribute)) {
        element.setAttribute(key, value);
    }
    return element;
}
