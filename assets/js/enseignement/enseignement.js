// import de la fonction fetchJSON pour faire des requetes AJAX
import { fetchJSON } from "unctions.js";

const enseigneElement = fetchJSON("/enseignement");
enseigneElement.then((data) => {
    data.forEach((item) => {
        console.log(item.teacher.lastname);
    });
});
