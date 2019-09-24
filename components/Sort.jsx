import React from 'react'

export class Sort extends React.Component {
    onClickSort(props) {
        let sort = this.state.Order[props.id];
        if (undefined == sort) {
            this.state.Order[props.id] = '-desc'
        } else if ('-desc' == sort) {
            this.state.Order[props.id] = '-asc'
        } else {
            delete this.state.Order[props.id]
        }
        fetch(props.link,
            {
                body: JSON.stringify(this.state),
                headers: {Accept: 'application/json', 'Content-Type': 'application/json'},
                method: 'POST'
            }).then(
            response => response.json()).then(state => {
            this.setState(state)
        })
    }

    Sort(props, state) {
        return <th className={'fa-hover sort'} key={props.id + '-sort'} onClick={this.onClickSort.bind(this, props)}>
            <a className={'fa-hover'} href={'javascript:;'}>
                {props.label}<i aria-hidden={'true'} className={state ? 'fa fa-sort' + state : 'fa fa-sort'}></i>
            </a>
        </th>
    }
}
