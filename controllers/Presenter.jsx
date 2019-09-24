import React from 'react'

export default class Presenter extends React.Component {
    constructor(props) {
        super(props)
    }

    Build(props) {
        for (let key in props) {
            if ('object' == typeof (props[key])) {
                props[key].id = key
            }
        }
        return props
    }
}
