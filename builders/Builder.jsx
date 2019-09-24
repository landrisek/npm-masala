export function Builder(callback, id, state) {
    let controller = document.getElementById(id);
    fetch(controller.getAttribute('data-link'),
        {
            body: JSON.stringify(state),
            headers: {
                Accept: 'application/json',
                'Access-Control-Request-Headers': 'content-type',
                'Content-Type': 'application/json'
            },
            method: 'POST'
        }).then(response => response.json()).then(props => {
        for (let key in props) {
            if ('object' == typeof (props[key])) {
                props[key].id = key
            }
        }
        callback(controller, props)
    }).catch(error => {
        console.log(error)
    })
}
