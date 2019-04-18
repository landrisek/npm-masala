import React from 'react'

var INVALID = {}

function invalidate(invalid, id, props) {
    if(invalid && undefined == INVALID[id]) {
        INVALID[id] = {[props]: true}
        return true
    } else if(undefined == INVALID[id]) {
    } else if(invalid) {
        return INVALID[id][props] = true
    } else if(false == invalid && undefined != INVALID[id] && undefined != INVALID[id][props]) {
        delete INVALID[id][props]
    }
}

export function Boolean(id, props, state) {
    if(invalidate(state, id, props.message)) {
        return <div className={'alert alert-warning alert-dismissible show'}>{props.message}</div>
    }
}

export function DateTime(id, props, state) {
    var invalid = undefined != state && state.length > 0 && isNaN(Date.parse(state))
    if(invalidate(invalid, id, props)) {
        return <div>{props}</div>
    }
}

export function Email(id, props, state) {
    var invalid = undefined != state && state.length > 0 && false == /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(String(state).toLowerCase())
    if(invalidate(invalid, id, props)) {
        return <div>{props}</div>
    }
}

export function Equal(id, props, state, value) {
    var invalid = state != value
    if(invalidate(invalid, id, props)) {
        return <div>{props}</div>
    }
}

export function Message(state) {
    return <div className={'message'}>{state}</div>
}

export function Minimum(id, props, state) {
    var invalid = undefined == state || props.value > state.length
    if(invalidate(invalid, id, props.message)) {
        return <div>{props.message}</div>
    }
}

export function Phone(id, props, state) {
    var invalid = undefined != state && state.length > 0 && false == /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{3,6}$/im.test(state)
    if(invalidate(invalid, id, props)) {
        return <div>{props}</div>
    }
}

export function Required(id, props, state) {
    var invalid = undefined == state || 0 == state.length
    if(invalidate(invalid, id, props)) {
        return <div className={'alert alert-warning alert-dismissible show'}>{props.message}</div>
    }
}

export function Submit(id, props, onClick) {
    for(var key in INVALID[id]) {
        return
    }
    return <input className={'btn btn-success'}
                  onClick={onClick}
                  style={{marginTop:'10px'}}
                  value={props.label}
                  type={'button'} />
}