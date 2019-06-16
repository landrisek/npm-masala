import React from 'react'

export class Checkbox extends React.Component {
    constructor(props) {
        super(props)
    }
    Checkbox(props, state) {
        return <label style={{marginRight:'10px'}}>
            <input checked={'1' == state ? 'checked' : ''}
                   onChange={this.onChangeCheckbox.bind(this, props)}
                   style={{marginRight:'10px'}}
                   type={'checkbox'}
                   value={state} />{props.label}
        </label>
    }
    onChangeCheckbox(props, event) {
        if(1 == event.target.value) {
            this.setState(this.OnChangeCheckbox(props, 0))
        } else {
            this.setState(this.OnChangeCheckbox(props, 1))
        }
    }
    OnChangeCheckbox(props, state) {
        return {[props.id]:String(state)}
    }
}