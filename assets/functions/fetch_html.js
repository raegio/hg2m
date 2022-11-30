export default function(resource, callback, params) {
    const options = undefined !== params ? { method: 'POST', body: params } : {};
    fetch(resource, options)
        .then(response => {
            if (!response.ok) {
                throw new Error("Failed to obtain a response from the network.");
            }
            return response.text();
        })
        .then(text => {
            const parser = new DOMParser(),
                doc = parser.parseFromString(text, "text/html");
            callback(doc);
        })
        .catch (error => {
            console.error("Failed to fetch the response.", error);
        })
    ;
};
