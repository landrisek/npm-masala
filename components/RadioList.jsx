import React from 'react'

export class RadioList extends React.Component {
    onChangeRadio(props, event) {
        this.setState({[props.id]:event.target.value})
    }
    RadioList(props, state) {
        let container = []
        for(let key in props.data) {
            container.push(<>&nbsp;<input checked={key == state} onChange={this.onChangeRadio.bind(this, props)} value={key} type={'radio'} />&nbsp;{props.data[key]}</>)
        }
        return container
    }
}