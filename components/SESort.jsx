import React from 'react'

export class SESort extends React.Component {
    onClickSESort(props) {
        let sort = this.state.Order[props.id];
        if (undefined == sort) {
            this.state.Order[props.id] = 'desc'
        } else if ('desc' == sort) {
            this.state.Order[props.id] = 'asc'
        } else {
            delete this.state.Order[props.id]
        }
        let client = new EventSource(this.props.link + '?masala=' + JSON.stringify(this.state));
        let self = this;
        client.onmessage = function (message) {
            let state = JSON.parse(message.data);
            self.setState({[message.lastEventId]: state[message.lastEventId]});
            self.OnClickSESort(props, message.lastEventId, this);
            self.buildUrl()
        }
    }

    OnClickSESort(props, state, event) {
        if ('Paginator' == state) {
            event.close()
        }
    }

    SESort(props, state) {
        return <th className={'fa-hover sort'} key={props.id + '-sort'} onClick={this.onClickSESort.bind(this, props)}>
            <a className={'fa-hover'} href={'javascript:;'}>
                {props.label}<i aria-hidden={'true'} className={state ? 'fa fa-sort-' + state : 'fa fa-sort'}></i>
            </a>
        </th>
    }
}
