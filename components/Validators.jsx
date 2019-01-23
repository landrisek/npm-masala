import React from 'react'

var INVALID = {}

function invalidate(clause, id, props) {
    if(clause && undefined == INVALID[id]) {
        INVALID[id] = {[props]: true}
        return true
    } else if(undefined == INVALID[id]) {
    } else if(clause) {
        return INVALID[id][props] = true
    } else if(false == clause && undefined != INVALID[id] && undefined != INVALID[id][props]) {
        delete INVALID[id][props]
    }
}

export function Email(id, props, state) {
    var clause = undefined != state && state.length > 0 && false == /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(String(state).toLowerCase())
    if(invalidate(clause, id, props)) {
        return <div>{props}</div>
    }
}

export function Equal(id, props, state, value) {
    var clause = state != value
    if(invalidate(clause, id, props)) {
        return <div>{props}</div>
    }
}

export function Message(state) {
    return <div className={'message'}>{state}</div>
}

export function Minimum(id, props, state) {
    var clause = undefined == state || props.value > state.length
    if(invalidate(clause, id, props.message)) {
        return <div>{props.message}</div>
    }
}

export function Phone(id, props, state) {
    var clause = undefined != state && state.length > 0 && false == /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{3,6}$/im.test(state)
    if(invalidate(clause, id, props)) {
        return <div>{props}</div>
    }
}

export function Required(id, props, state) {
    var clause = undefined == state || 0 == state.length
    if(invalidate(clause, id, props)) {
        return <div>{props}</div>
    }
}

export function Submit(id, props, state, onClick) {
    for(var key in INVALID[id]) {
        return
    }
    console.log(state)
    return <input className={state ? 'waiting' : 'submit button'}
                  data-id={props.id}
                  onClick={onClick}
                  value={state ? '                 ' : props.label}
                  type={'button'} />
}