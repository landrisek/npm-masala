import React from 'react'

export function Invalid(props, state) {
    if (state) {
        return <div className={'alert alert-warning alert-dismissible show'}>{props.message}</div>
    }
}
