import React from 'react'

export class SEPaginator extends React.Component {
    onClickSEPaginator(props, event) {
        event.preventDefault()
        this.state.Clicked.Paginator = 'in progress...'
        this.setState({Clicked:this.state.Clicked})
        this.state.Paginator.Current = parseInt(event.target.getAttribute('data-page'))
        let client = new EventSource(this.props.link + '/' + props.id + '?masala=' + JSON.stringify(this.state))
        let self = this
        client.onmessage = function (message) {
            let clicked = self.state.Clicked;
            delete clicked[props.id];
            let state = JSON.parse(message.data);
            self.setState({[message.lastEventId]:state[message.lastEventId],Clicked:clicked});
            self.buildUrl()
            self.OnClickSEPaginator(props, message.lastEventId, this);
        }
    }
    OnClickSEPaginator(props, state, event) {
        if(props.id == state) {
            event.close()
        }
    }
    SEPaginator(props, state) {
        let current = parseInt(state.Current)
        let pages = []
        for(let page = 1; page <= parseInt(state.Last); page++) {
            if(page == current) {
                pages.push(<li className={'page-item active'} key={page}><a className={'page-link'} data-page={page} title={props.page + ' '  + page}>{page}</a></li>)
            } else if(current - 10 == page) {
                pages.push(<li key={'previous-page'}><a aria-label={'Previous'} className={'page-link'} data-page={current - 1} onClick={this.onClickSEPaginator.bind(this, props)} title={props.previous}>
                    <span aria-hidden={'true'} data-page={current - 1}>&laquo;</span></a></li>)
            } else if(current + 10 == page) {
                pages.push(<li key={'next-page'}><a aria-label={'Next'} className={'page-link'} data-page={current + 1} onClick={this.onClickSEPaginator.bind(this, props)} title={props.next}>
                    <span aria-hidden={'true'} data-page={current + 1}>&raquo;</span></a></li>)
            } else if(page < current - 10 || page > current + 10) {
            } else {
                pages.push(<li className={'page-item'} key={page}><a className={'page-link'} data-page={page} onClick={this.onClickSEPaginator.bind(this, props)} title={props.page + ' ' + page}>{page}</a></li>)
            }
        }
        pages.push(<li className={'page-item'} key={'sum'}><a className={'page-link'} title={props.sum + ' ' + state.Sum}>{props.sum + ' ' + state.Sum}
            <i className={'fa fa-fw fa-database'}></i>
        </a></li>)
        let paginator = <ul className={'pagination'}>{pages}</ul>
        return this.IsClicked(props, paginator)
    }
}

