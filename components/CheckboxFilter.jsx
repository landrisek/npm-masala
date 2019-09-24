import React from 'react'

export class CheckboxFilter extends React.Component {
    constructor(props) {
        super(props)
    }

    CheckboxFilter(props, state) {
        return <label style={{marginRight: '10px'}}>
            <input checked={'1' == state ? 'checked' : ''}
                   onChange={this.onChangeCheckboxFilter.bind(this, props)}
                   style={{marginRight: '10px'}}
                   type={'checkbox'}
                   value={state}/>{props.label}
        </label>
    }

    onChangeCheckboxFilter(props, event) {
        if (1 == event.target.value) {
            this.setState(this.OnChangeCheckboxFilter(props, 0))
        } else {
            this.setState(this.OnChangeCheckboxFilter(props, 1))
        }
    }

    OnChangeCheckboxFilter(props, state) {
        let where = this.state.Where;
        where[props.id] = String(state);
        return {[props.id]: String(state)}
    }
}
