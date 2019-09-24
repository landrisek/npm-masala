export function Build(data) {
    let props = JSON.parse(data);
    for (let key in props) {
        if ('object' == typeof (props[key])) {
            props[key].id = key
        }
    }
    return props
}
